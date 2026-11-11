<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LeadController;
use App\Jobs\ProcessIncomingLeadMessage;
use App\Models\Lead;
use App\Models\LeadMessage;
use App\Models\MetaAdIntent;
use App\Models\User;
use App\Services\LeadAlertService;
use App\Services\LeadConversationService;
use App\Services\LeadQualificationService;
use App\Services\MetaAdAttributionService;
use App\Services\MetaWhatsAppService;
use App\Services\LeadInterpreter;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * Meta / WhatsApp lead qualification:
 * webhook verification + signature, dedupe, follow-up handling, automation
 * completion, admin reopen/release, retention and provider label limits.
 *
 * Follows the suite convention: fixtures live in a transaction that is rolled
 * back and external services are mocked/faked.
 */
class LeadQualificationTest extends TestCase
{
    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('leads')
            || !Schema::hasTable('lead_messages')
            || !Schema::hasTable('meta_ad_intents')
            || !Schema::hasColumn('leads', 'automation_completed_at')
            || !Schema::hasColumn('leads', 'alert_status')) {
            $this->markTestSkipped('Run `php artisan migrate` to enable lead qualification tests.');
        }

        config([
            'meta_whatsapp.app_secret' => 'test-secret',
            'meta_whatsapp.verify_token' => 'test-verify-token',
            'meta_whatsapp.access_token' => 'test-access-token',
            'meta_whatsapp.phone_number_id' => '123456789',
            'meta_whatsapp.base_url' => 'https://graph.facebook.com',
            'meta_whatsapp.graph_version' => 'v24.0',
            'lead_qualification.alert_email' => null,
            'lead_qualification.alert_phone' => null,
            'lead_qualification.alert_sms_enabled' => false,
        ]);

        Queue::fake();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Webhook
    // ------------------------------------------------------------------

    public function test_webhook_verification_returns_challenge(): void
    {
        $response = $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=abc123');

        $response->assertOk();
        $this->assertSame('abc123', $response->getContent());
    }

    public function test_webhook_rejects_an_invalid_signature(): void
    {
        $phone = $this->phone();
        $payload = $this->payload($phone, $this->wamid(), [], 'I need an accountant');

        $response = $this->signedPost($payload, 'sha256=' . str_repeat('0', 64));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('leads', ['whatsapp_id' => $phone]);
    }

    public function test_webhook_rejects_a_wrong_verify_token(): void
    {
        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=not-the-token&hub.challenge=abc123')
            ->assertStatus(403);
    }

    public function test_webhook_stores_the_message_and_queues_processing(): void
    {
        $phone = $this->phone();
        $payload = $this->payload($phone, $this->wamid(), [], 'I need an accountant');

        $response = $this->signedPost($payload);

        $response->assertOk()->assertJson(['ok' => true]);
        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $this->assertDatabaseHas('lead_messages', [
            'lead_id' => $lead->id,
            'direction' => 'inbound',
            'body' => 'I need an accountant',
            'delivery_status' => 'received',
        ]);
        Queue::assertPushed(ProcessIncomingLeadMessage::class, fn($job) => $job->leadId === $lead->id);
    }

    public function test_webhook_ignores_duplicate_provider_message_ids(): void
    {
        $phone = $this->phone();
        $messageId = $this->wamid();
        $payload = $this->payload($phone, $messageId, [], 'Hello');

        $this->signedPost($payload)->assertOk();
        $this->signedPost($payload)->assertOk();

        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $this->assertSame(1, LeadMessage::where('lead_id', $lead->id)->count());
        Queue::assertPushed(ProcessIncomingLeadMessage::class, 1);
    }

    public function test_concurrent_duplicate_webhook_delivery_returns_ok(): void
    {
        $phone = $this->phone();
        $messageId = $this->wamid();
        $payload = $this->payload($phone, $messageId, [], 'Hello');

        // Simulate another worker winning the race after the existence check
        // but before this worker inserts the row.
        $event = 'eloquent.creating: ' . LeadMessage::class;
        Event::listen($event, function (LeadMessage $model) use ($messageId) {
            if ($model->provider_message_id !== $messageId) return;

            DB::table('lead_messages')->insert([
                'lead_id' => $model->lead_id,
                'provider_message_id' => $messageId,
                'direction' => 'inbound',
                'sender_type' => 'prospect',
                'message_type' => 'text',
                'body' => 'duplicate',
                'delivery_status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $this->signedPost($payload)->assertOk();
        } finally {
            Event::forget($event);
        }

        $this->assertSame(1, LeadMessage::where('provider_message_id', $messageId)->count());
    }

    public function test_manual_takeover_stores_messages_without_queueing_the_bot(): void
    {
        $phone = $this->phone();
        $lead = $this->makeLead([
            'whatsapp_id' => $phone,
            'human_takeover' => true,
        ]);

        $this->signedPost($this->payload($phone, $this->wamid(), [], 'Any update?'))->assertOk();

        $this->assertDatabaseHas('lead_messages', ['lead_id' => $lead->id, 'body' => 'Any update?']);
        Queue::assertNotPushed(ProcessIncomingLeadMessage::class);
    }

    public function test_completed_lead_replies_are_still_queued_for_follow_up_alerting(): void
    {
        $phone = $this->phone();
        $lead = $this->makeCompletedLead(['whatsapp_id' => $phone]);

        $this->signedPost($this->payload($phone, $this->wamid(), [], 'Can you call me tomorrow?'))->assertOk();

        Queue::assertPushed(ProcessIncomingLeadMessage::class, fn($job) => $job->leadId === $lead->id);
    }

    public function test_new_ad_click_after_completion_creates_a_fresh_lead_and_keeps_old_attribution(): void
    {
        $phone = $this->phone();
        $old = $this->makeCompletedLead([
            'whatsapp_id' => $phone,
            'click_id' => 'OLD-CLID',
            'ad_id' => 'OLD-AD',
            'campaign_name' => 'Old campaign',
        ]);

        // Intent comes from the explicit mapping, never the advert copy.
        MetaAdIntent::create(['ad_id' => 'NEW-AD', 'intent' => 'change_accountant']);

        $referral = [
            'source_type' => 'ad',
            'source_id' => 'NEW-AD',
            'ctwa_clid' => 'NEW-CLID',
            'headline' => 'Switch accountant today',
            'body' => 'Speak to HD Accountancy',
            'source_url' => 'https://fb.me/example',
        ];

        $this->signedPost($this->payload($phone, $this->wamid(), $referral, 'Hi'))->assertOk();

        $leads = Lead::where('whatsapp_id', $phone)->orderBy('id')->get();
        $this->assertCount(2, $leads);

        $new = $leads->last();
        $this->assertSame('meta_ad_whatsapp', $new->source);
        $this->assertSame('NEW-AD', $new->ad_id);
        $this->assertSame('NEW-CLID', $new->click_id);
        $this->assertSame('change_accountant', $new->intent);
        $this->assertSame('person_type', $new->conversation_step);
        $this->assertNull($new->automation_completed_at);

        $old->refresh();
        $this->assertSame('OLD-AD', $old->ad_id);
        $this->assertSame('OLD-CLID', $old->click_id);
        $this->assertSame('Old campaign', $old->campaign_name);
    }

    public function test_unknown_ad_does_not_guess_intent_from_marketing_copy(): void
    {
        $phone = $this->phone();
        $referral = [
            'source_type' => 'ad',
            'source_id' => 'UNMAPPED-AD',
            'ctwa_clid' => 'CLID-1',
            // HD's current advert copy. The old regex would have routed this to
            // tax_vat_payroll purely because it mentions VAT/payroll/accounts.
            'headline' => 'Your accountant, sorted',
            'body' => 'VAT registered, payroll and accounts done for £49 a month. Talk to a real accountant.',
            'source_url' => 'https://fb.me/general',
        ];

        $this->signedPost($this->payload($phone, $this->wamid(), $referral, 'Hi, I want to know more'))->assertOk();

        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $this->assertNull($lead->intent);
        $this->assertSame('intent', $lead->conversation_step);
        // The advert copy is still stored for attribution/audit.
        $this->assertSame('UNMAPPED-AD', $lead->ad_id);
        $this->assertNotNull($lead->ad_headline);
    }

    public function test_mapped_ad_skips_only_the_intent_question(): void
    {
        MetaAdIntent::create(['ad_id' => 'LANDLORD-AD', 'intent' => 'landlord_property']);
        $phone = $this->phone();

        $this->signedPost($this->payload($phone, $this->wamid(), [
            'source_type' => 'ad',
            'source_id' => 'LANDLORD-AD',
            'headline' => 'Landlords: tax returns sorted',
        ], 'Hi'))->assertOk();

        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $this->assertSame('landlord_property', $lead->intent);
        $this->assertSame('person_type', $lead->conversation_step);
    }

    public function test_inactive_ad_mapping_falls_back_to_the_first_question(): void
    {
        MetaAdIntent::create(['ad_id' => 'PAUSED-AD', 'intent' => 'change_accountant', 'is_active' => false]);
        $phone = $this->phone();

        $this->signedPost($this->payload($phone, $this->wamid(), [
            'source_type' => 'ad',
            'source_id' => 'PAUSED-AD',
        ], 'Hi'))->assertOk();

        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $this->assertNull($lead->intent);
        $this->assertSame('intent', $lead->conversation_step);
    }

    public function test_non_ad_referrals_are_not_recorded_as_ad_attribution(): void
    {
        $phone = $this->phone();
        $referral = [
            'source_type' => 'post',
            'source_id' => 'POST-1',
            'source_url' => 'https://facebook.com/post/1',
        ];

        $this->signedPost($this->payload($phone, $this->wamid(), $referral, 'Hi'))->assertOk();

        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $this->assertSame('whatsapp', $lead->source);
        $this->assertNull($lead->ad_id);
        $this->assertNull($lead->click_id);
    }

    public function test_job_marks_a_processed_message_and_ignores_wrong_leads(): void
    {
        $lead = $this->makeLead();
        $message = $this->makeInbound($lead, 'Hello');

        $conversation = Mockery::mock(LeadConversationService::class);
        $conversation->shouldReceive('process')->once()->with(
            Mockery::on(fn($m) => $m instanceof LeadMessage && $m->id === $message->id)
        );

        $job = new ProcessIncomingLeadMessage($message->id, $lead->id);
        $job->handle($conversation);

        $this->assertSame('processed', $message->fresh()->delivery_status);

        // A stale/mismatched lead id must not process the message.
        $otherLead = $this->makeLead();
        $otherMessage = $this->makeInbound($otherLead, 'Hello');
        $ignored = Mockery::mock(LeadConversationService::class);
        $ignored->shouldNotReceive('process');
        (new ProcessIncomingLeadMessage($otherMessage->id, $lead->id))->handle($ignored);

        $this->assertSame('received', $otherMessage->fresh()->delivery_status);
    }

    public function test_job_failure_marks_the_message_and_alerts_the_team(): void
    {
        $lead = $this->makeLead();
        $message = $this->makeInbound($lead, 'Hello');

        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notifyProcessingFailure')->once()->with(
            Mockery::on(fn($l) => $l instanceof Lead && $l->id === $lead->id),
            Mockery::type('string')
        );
        $this->app->instance(LeadAlertService::class, $alerts);

        (new ProcessIncomingLeadMessage($message->id, $lead->id))->failed(new \RuntimeException('provider down'));

        $message->refresh();
        $this->assertSame('failed', $message->delivery_status);
        $this->assertStringContainsString('provider down', (string) $message->failure_reason);
    }

    // ------------------------------------------------------------------
    // Qualification / completion flow
    // ------------------------------------------------------------------

    public function test_terminal_lead_finishes_once_and_marks_automation_complete(): void
    {
        $lead = $this->makeLead([
            'qualification_status' => 'GENUINE',
            'conversation_step' => 'complete',
            'automation_completed_at' => null,
        ]);
        $message = $this->makeInbound($lead, 'Thanks');

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendText')->once()->with(
            Mockery::on(fn($arg) => $arg instanceof Lead && $arg->id === $lead->id),
            Mockery::type('string')
        );

        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notify')->once()->with(
            Mockery::on(fn($arg) => $arg instanceof Lead && $arg->id === $lead->id)
        );
        $alerts->shouldReceive('notifyFollowUp')->once();

        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldNotReceive('interpret');
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldNotReceive('enrich');

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($message);

        $lead->refresh();
        $this->assertNotNull($lead->automation_completed_at);
        $this->assertFalse($lead->human_takeover);

        // A later reply must alert the team, not run the closing message again.
        $second = $this->makeInbound($lead, 'One more question');
        $service->process($second);

        $this->assertSame('GENUINE', $lead->fresh()->qualification_status);
    }

    public function test_completed_lead_follow_up_does_not_run_the_interpreter(): void
    {
        $lead = $this->makeCompletedLead();
        $message = $this->makeInbound($lead, 'Can we speak?');

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldNotReceive('sendText');

        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notifyFollowUp')->once()->with(
            Mockery::on(fn($l) => $l instanceof Lead && $l->id === $lead->id),
            Mockery::on(fn($m) => $m instanceof LeadMessage && $m->id === $message->id)
        );
        $alerts->shouldNotReceive('notify');

        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldNotReceive('interpret');
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldNotReceive('enrich');

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($message);

        $this->assertSame('GENUINE', $lead->fresh()->qualification_status);
    }

    public function test_completed_non_sales_leads_do_not_generate_follow_up_alerts(): void
    {
        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldNotReceive('sendText');
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldNotReceive('notifyFollowUp');
        $alerts->shouldNotReceive('notify');
        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldNotReceive('interpret');
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldNotReceive('enrich');

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        foreach (['REJECTED', 'NON_SALES'] as $status) {
            $lead = $this->makeCompletedLead(['qualification_status' => $status]);
            $service->process($this->makeInbound($lead, 'Still spam'));
        }
    }

    public function test_follow_up_alerts_are_throttled_per_lead(): void
    {
        $lead = $this->makeCompletedLead();

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldNotReceive('sendText');
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notifyFollowUp')->once();
        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldNotReceive('interpret');
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldNotReceive('enrich');

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($this->makeInbound($lead, 'First follow-up'));
        $service->process($this->makeInbound($lead, 'Second follow-up'));
    }

    public function test_manual_takeover_suppresses_all_automation(): void
    {
        $lead = $this->makeLead(['human_takeover' => true]);
        $message = $this->makeInbound($lead, 'Hello?');

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldNotReceive('sendText');
        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldNotReceive('interpret');
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldNotReceive('enrich');
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldNotReceive('notify');
        $alerts->shouldNotReceive('notifyFollowUp');

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($message);
    }

    public function test_releasing_takeover_does_not_repeat_a_completed_qualification(): void
    {
        $lead = $this->makeCompletedLead(['human_takeover' => true]);

        app(LeadController::class)->release($lead);

        $lead->refresh();
        $this->assertFalse($lead->human_takeover);
        $this->assertNotNull($lead->automation_completed_at);

        $message = $this->makeInbound($lead, 'Following up');

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldNotReceive('sendText');
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldNotReceive('notify');
        $alerts->shouldReceive('notifyFollowUp')->once();
        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldNotReceive('interpret');
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldNotReceive('enrich');

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($message);
    }

    public function test_reopening_qualification_clears_the_completion_marker_and_answers(): void
    {
        $lead = $this->makeCompletedLead([
            'qualified_at' => now(),
            'intent' => 'change_accountant',
            'person_type' => 'owner_director',
            'current_accountant' => 'yes_change',
            'contact_timeframe' => 'asap',
            'qualification_data' => ['intent' => 'change_accountant'],
        ]);

        app(LeadController::class)->update(new Request([
            'qualification_status' => 'QUALIFYING',
        ]), $lead);

        $lead->refresh();
        $this->assertNull($lead->automation_completed_at);
        $this->assertNull($lead->qualified_at);
        $this->assertNull($lead->intent);
        $this->assertNull($lead->person_type);
        $this->assertNull($lead->current_accountant);
        $this->assertNull($lead->contact_timeframe);
        $this->assertNull($lead->qualification_data);
        $this->assertSame('intent', $lead->conversation_step);
        $this->assertSame('QUALIFYING', $lead->qualification_status);
    }

    public function test_reopening_mapped_lead_clears_intent_source_and_allows_free_text_intent(): void
    {
        // A lead created from an explicit ad mapping has intent_source = 'ad_mapping'
        $lead = $this->makeCompletedLead([
            'intent' => 'change_accountant',
            'intent_source' => 'ad_mapping',
            'qualification_data' => ['intent' => 'change_accountant'],
        ]);

        // Admin reopens qualification
        app(LeadController::class)->update(new Request([
            'qualification_status' => 'QUALIFYING',
        ]), $lead);

        $lead->refresh();
        // Both intent and intent_source must be cleared
        $this->assertNull($lead->intent);
        $this->assertNull($lead->intent_source);
        $this->assertSame('intent', $lead->conversation_step);
        $this->assertSame('QUALIFYING', $lead->qualification_status);

        // Now process a free-text inbound answer to Q1
        $message = $this->makeInbound($lead, 'I need help with payroll');
        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendButtons')->once(); // advances to person_type step
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldNotReceive('notify');
        $this->conversation($whatsApp, $alerts)->process($message);

        $lead->refresh();
        // The ad_mapping guard must NOT block free-text intent assignment
        $this->assertSame('tax_vat_payroll', $lead->intent);
    }

    public function test_button_answer_does_not_overwrite_other_qualification_fields(): void
    {
        $lead = $this->makeLead([
            'intent' => 'change_accountant',
            'conversation_step' => 'person_type',
            'qualification_status' => 'QUALIFYING',
        ]);
        $message = $this->makeInbound($lead, 'Self-employed');
        $message->forceFill([
            'interactive_payload' => ['id' => 'self_employed_landlord', 'title' => 'Self-employed'],
        ])->save();

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendList')->once();
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldNotReceive('notify');
        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldReceive('interpret')->once()->andReturn([
            'intent' => 'landlord_property',
            'person_type' => 'self_employed_landlord',
            'current_accountant' => 'unknown',
            'contact_timeframe' => 'unknown',
            'classification_hint' => 'unknown',
            'summary' => 'Self-employed prospect.',
        ]);
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldReceive('enrich')->once();

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($message);

        $lead->refresh();
        $this->assertSame('change_accountant', $lead->intent);
        $this->assertSame('self_employed_landlord', $lead->person_type);
        $this->assertSame('contact_timeframe', $lead->conversation_step);
    }

    public function test_job_retry_settings_tolerate_overlap_releases(): void
    {
        $job = new ProcessIncomingLeadMessage(1, 1);

        $this->assertSame(0, $job->tries);
        $this->assertSame(3, $job->maxExceptions);
        $this->assertInstanceOf(\DateTimeInterface::class, $job->retryUntil());
        $this->assertSame(180, (int) config('queue.connections.database.retry_after'));
        $this->assertGreaterThan($job->timeout, (int) config('queue.connections.database.retry_after'));
    }

    public function test_reversing_a_closed_sales_status_clears_closed_at(): void
    {
        $lead = $this->makeLead([
            'sales_status' => 'CLOSED',
            'closed_at' => now()->subDay(),
        ]);

        app(LeadController::class)->update(new Request([
            'sales_status' => 'CONTACTED',
        ]), $lead);

        $this->assertNull($lead->fresh()->closed_at);
    }

    public function test_review_hint_is_honoured(): void
    {
        $qualifier = app(LeadQualificationService::class);
        $lead = new Lead(['intent' => null, 'person_type' => null, 'contact_timeframe' => null]);

        $this->assertSame('REVIEW', $qualifier->classify($lead, ['classification_hint' => 'review']));
    }

    public function test_rule_based_parser_extracts_common_enquiries(): void
    {
        $interpreter = app(LeadInterpreter::class);

        $change = $interpreter->interpret('I need a new accountant asap, I run a company');
        $this->assertSame('change_accountant', $change['intent']);
        $this->assertSame('owner_director', $change['person_type']);
        $this->assertSame('asap', $change['contact_timeframe']);

        $nonSales = $interpreter->interpret('We offer SEO and web design services');
        $this->assertSame('non_sales', $nonSales['intent']);
        $this->assertSame('non_sales', $nonSales['classification_hint']);

        $existing = $interpreter->interpret('I am already a client of yours');
        $this->assertSame('existing_client', $existing['intent']);

        // Short keywords must match whole words only: "decision" is not CIS,
        // "private" is not VAT, and "seoul" is not SEO.
        $decision = $interpreter->interpret('We need help making a decision');
        $this->assertSame('unknown', $decision['intent']);

        $private = $interpreter->interpret('This is a private matter');
        $this->assertSame('unknown', $private['intent']);

        $cv = $interpreter->interpret('Can you review my CV?');
        $this->assertSame('non_sales', $cv['intent']);

        // Review R1: unambiguous job-seeker / supplier wording is NON_SALES.
        foreach ([
            'We have a vacancy in our sales team',
            'Several vacancies available',
            'I am looking for work',
            'Is there a job available?',
            'I would like an internship',
            'I run an employment agency',
        ] as $phrase) {
            $this->assertSame('non_sales', $interpreter->interpret($phrase)['intent'], $phrase);
        }

        // Ambiguous wording with no accounting need must never auto-reject a
        // possible client: REVIEW, so a human decides.
        foreach ([
            'I need help with employment status for my staff',
            'I want to change career and set up a company',
        ] as $phrase) {
            $result = $interpreter->interpret($phrase);
            $this->assertNotSame('non_sales', $result['intent'], $phrase);
            $this->assertSame('review', $result['classification_hint'], $phrase);
        }

        // Ambiguous wording WITH a clear accounting need is a genuine enquiry.
        $this->assertSame('tax_vat_payroll', $interpreter->interpret('We are hiring and need help with payroll')['intent']);

        // "self-employment" must stay a genuine prospect, not a job seeker.
        $this->assertSame('unknown', $interpreter->interpret('I have income from self-employment')['intent']);
    }

    /**
     * Review section 6: an existing HD client is marked EXISTING_CLIENT, stops
     * sales qualification and still alerts the team.
     */
    /**
     * Review 10 Nov, section 3: a genuine business can mention recruitment,
     * suppliers or vacancies and still need accounting help. Those must never
     * be rejected as non-sales.
     */
    public function test_genuine_businesses_mentioning_recruitment_or_vacancies_are_not_non_sales(): void
    {
        $interpreter = app(LeadInterpreter::class);
        $qualifier = app(LeadQualificationService::class);

        foreach ([
            'I run a recruitment agency and need an accountant.',
            'I am a food supplier and need bookkeeping.',
            'We have several vacancies and need help with payroll.',
        ] as $phrase) {
            $result = $interpreter->interpret($phrase);
            $this->assertNotSame('non_sales', $result['intent'], $phrase);

            $lead = new Lead(['intent' => $result['intent']]);
            $this->assertNotSame('NON_SALES', $qualifier->classify($lead, $result), $phrase);
        }

        // Real job seekers are still closed politely.
        foreach (['Do you have any vacancies?', 'I am looking for work.'] as $phrase) {
            $result = $interpreter->interpret($phrase);
            $this->assertSame('non_sales', $result['intent'], $phrase);
            $this->assertSame('NON_SALES', $qualifier->classify(new Lead(), $result), $phrase);
        }
    }

    /**
     * Review 10 Nov, section 2: the explicit Ad ID mapping is the authoritative
     * campaign route and the pre-filled WhatsApp text must not overwrite it.
     */
    public function test_mapped_ad_intent_survives_the_prefilled_whatsapp_text(): void
    {
        MetaAdIntent::create(['ad_id' => 'MAPPED-12345', 'intent' => 'change_accountant']);

        $phone = $this->phone();
        $referral = [
            'source_type' => 'ad',
            'source_id' => 'MAPPED-12345',
            'ctwa_clid' => 'CLID-MAPPED',
        ];

        // The advert is mapped to Change Accountant, but the pre-filled text
        // mentions payroll and accounts; the old regex took it over.
        $this->signedPost($this->payload($phone, $this->wamid(), $referral, 'I need help with payroll and accounts'))->assertOk();

        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $message = LeadMessage::where('lead_id', $lead->id)->firstOrFail();

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendButtons')->once();
        $alerts = Mockery::mock(LeadAlertService::class);

        $service = new LeadConversationService(
            $whatsApp,
            app(LeadInterpreter::class),
            app(LeadQualificationService::class),
            $alerts,
            app(MetaAdAttributionService::class)
        );
        $service->process($message);

        $lead->refresh();
        $this->assertSame('change_accountant', $lead->intent);
        $this->assertSame('ad_mapping', $lead->intent_source);
        $this->assertSame('MAPPED-12345', $lead->ad_id);
        $this->assertSame('MAPPED-12345', $lead->original_ad_id);
        // What the customer mentioned is kept as context, not as the route.
        $this->assertSame('tax_vat_payroll', $lead->qualification_data['mentioned_intent'] ?? null);
    }

    /**
     * Review 10 Nov, section 4: a later click on a different advert must not
     * rewrite the first-touch acquisition of an existing conversation.
     */
    public function test_later_ad_click_does_not_overwrite_first_touch_attribution(): void
    {
        $phone = $this->phone();

        // First touch: Campaign A / Ad 111.
        $this->signedPost($this->payload($phone, $this->wamid(), [
            'source_type' => 'ad', 'source_id' => '111', 'ctwa_clid' => 'CLID-A',
        ], 'Hi, I need an accountant'))->assertOk();

        $lead = Lead::where('whatsapp_id', $phone)->firstOrFail();
        $lead->update([
            'ad_id' => '111',
            'original_ad_id' => '111',
            'campaign_id' => 'CAMP-A',
            'original_campaign_id' => 'CAMP-A',
            'qualification_status' => 'GENUINE',
            'conversation_step' => 'complete',
            'automation_completed_at' => now(),
            'human_takeover' => true,
        ]);

        // Later touch by the same person: Campaign B / Ad 222.
        $this->signedPost($this->payload($phone, $this->wamid(), [
            'source_type' => 'ad', 'source_id' => '222', 'ctwa_clid' => 'CLID-B',
        ], 'Another question'))->assertOk();

        $lead->refresh();
        $this->assertSame('111', $lead->ad_id);
        $this->assertSame('111', $lead->original_ad_id);
        $this->assertSame('CAMP-A', $lead->campaign_id);
        $this->assertSame('CAMP-A', $lead->original_campaign_id);
        // The second referral is retained without corrupting first touch.
        $this->assertSame('222', $lead->latest_touch_ad_id);
        $this->assertSame('CLID-B', $lead->latest_touch_click_id);
        $this->assertSame('CLID-A', $lead->click_id);
        $this->assertNotNull($lead->latest_touch_at);

        // A lead that predates these columns keeps the attribution it already
        // has: the existing ad becomes the first touch, not the new click.
        $legacyPhone = $this->phone();
        $legacy = Lead::create([
            'name' => 'Legacy',
            'whatsapp_id' => $legacyPhone,
            'phone' => $legacyPhone,
            'source' => 'meta_ad_whatsapp',
            'ad_id' => '111',
            'click_id' => 'CLID-A',
            'campaign_id' => 'CAMP-A',
        ]);

        $this->signedPost($this->payload($legacyPhone, $this->wamid(), [
            'source_type' => 'ad', 'source_id' => '222', 'ctwa_clid' => 'CLID-B',
        ], 'Hello again'))->assertOk();

        $legacy->refresh();
        $this->assertSame('111', $legacy->ad_id);
        $this->assertSame('111', $legacy->original_ad_id);
        $this->assertSame('CLID-A', $legacy->original_click_id);
        $this->assertSame('CAMP-A', $legacy->original_campaign_id);
        $this->assertSame('222', $legacy->latest_touch_ad_id);
    }

    public function test_existing_client_stops_qualification_and_alerts_the_team(): void
    {
        $lead = $this->makeLead();
        $message = $this->makeInbound($lead, 'I am already a client of yours');

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendText')->once();
        $whatsApp->shouldNotReceive('sendList');
        $whatsApp->shouldNotReceive('sendButtons');

        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notify')->once()->with(
            Mockery::on(fn($arg) => $arg instanceof Lead && $arg->id === $lead->id)
        );

        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldReceive('enrich')->once();

        $service = new LeadConversationService(
            $whatsApp,
            app(LeadInterpreter::class),
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($message);

        $lead->refresh();
        $this->assertSame('EXISTING_CLIENT', $lead->qualification_status);
        $this->assertSame('complete', $lead->conversation_step);
        $this->assertNotNull($lead->automation_completed_at);
    }

    /**
     * Review test matrix: free-form staff replies are allowed inside the
     * 24-hour service window, blocked outside it, and the approved template
     * path still works outside the window.
     */
    public function test_free_form_staff_reply_needs_an_open_whatsapp_window(): void
    {
        $this->actingAs($this->makeUser(false));
        config(['meta_whatsapp.reengagement_template' => 'lead_reengagement', 'meta_whatsapp.reengagement_template_language' => 'en_GB']);

        $recent = $this->makeLead(['last_inbound_at' => now()->subHours(2)]);
        $stale = $this->makeLead(['last_inbound_at' => now()->subHours(30)]);

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendText')->once()->with(
            Mockery::on(fn($arg) => $arg instanceof Lead && $arg->id === $recent->id),
            'Hello from HD',
            'staff',
            Mockery::type('int')
        );
        $whatsApp->shouldReceive('sendTemplate')->once()->with(
            Mockery::on(fn($arg) => $arg instanceof Lead && $arg->id === $stale->id),
            'lead_reengagement',
            'en_GB',
            'staff',
            Mockery::type('int')
        );
        $this->swap(MetaWhatsAppService::class, $whatsApp);

        // Outside the window a free-form reply is refused and nothing changes.
        $this->post(route('admin.leads.send', $stale), ['message' => 'Hello from HD'])
            ->assertSessionHasErrors('message');
        $this->assertFalse($stale->fresh()->human_takeover);

        // Inside the window the free-form reply is delivered and takes over.
        $this->post(route('admin.leads.send', $recent), ['message' => 'Hello from HD'])
            ->assertSessionHas('success');
        $this->assertTrue($recent->fresh()->human_takeover);

        // The approved template is the supported path outside the window.
        $this->post(route('admin.leads.send-template', $stale))
            ->assertSessionHas('success');
        $this->assertTrue($stale->fresh()->human_takeover);
    }

    /**
     * A voice note or a photo of an HMRC letter cannot be answered with
     * buttons. It must reach a human instead of being counted as an unclear
     * answer and re-prompted.
     */
    public function test_media_messages_go_to_review_instead_of_being_re_prompted(): void
    {
        $lead = $this->makeLead([
            'intent' => 'tax_vat_payroll',
            'person_type' => 'owner_director',
            'current_accountant' => 'not_applicable',
            'conversation_step' => 'contact_timeframe',
            'last_inbound_at' => now(),
        ]);

        $message = LeadMessage::create([
            'lead_id' => $lead->id,
            'provider_message_id' => $this->wamid(),
            'direction' => 'inbound',
            'sender_type' => 'prospect',
            'message_type' => 'image',
            'body' => '[image message]',
            'delivery_status' => 'received',
            'provider_timestamp' => now(),
        ]);

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendText')->once();
        $whatsApp->shouldNotReceive('sendButtons');
        $whatsApp->shouldNotReceive('sendList');
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notify')->once();

        $this->conversation($whatsApp, $alerts)->process($message);

        $lead->refresh();
        $this->assertSame('REVIEW', $lead->qualification_status);
        $this->assertSame('complete', $lead->conversation_step);
        $this->assertSame(0, (int) $lead->unclear_replies);
        $this->assertNotNull($lead->automation_completed_at);
        $this->assertStringContainsString('needs a human', (string) $lead->qualification_summary);
    }

    /**
     * Take Over can land while a turn is already running. The bot must not send
     * a question or closing text over the human's reply.
     */
    public function test_takeover_during_a_running_turn_stops_the_bot_immediately(): void
    {
        $lead = $this->makeLead([
            'intent' => 'change_accountant',
            'person_type' => 'owner_director',
            'current_accountant' => 'yes_change',
            'contact_timeframe' => 'asap',
            'conversation_step' => 'contact_timeframe',
            'last_inbound_at' => now(),
        ]);
        $message = $this->makeInbound($lead, 'Owner, ASAP please');

        // The operator takes the conversation over in the middle of the turn.
        $interpreter = Mockery::mock(LeadInterpreter::class);
        $interpreter->shouldReceive('interpret')->once()->andReturnUsing(function () use ($lead) {
            $lead->update(['human_takeover' => true]);
            return ['intent' => 'unknown', 'person_type' => 'unknown', 'current_accountant' => 'unknown', 'contact_timeframe' => 'unknown', 'classification_hint' => 'unknown', 'summary' => '']; 
        });

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldNotReceive('sendText');
        $whatsApp->shouldNotReceive('sendButtons');
        $whatsApp->shouldNotReceive('sendList');
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldNotReceive('notify');

        $service = new LeadConversationService(
            $whatsApp,
            $interpreter,
            app(LeadQualificationService::class),
            $alerts,
            app(MetaAdAttributionService::class)
        );
        $service->process($message);

        $lead->refresh();
        $this->assertTrue($lead->human_takeover);
        // The turn is closed out so it can never restart later, but silently.
        $this->assertSame('GENUINE', $lead->qualification_status);
        $this->assertNotNull($lead->automation_completed_at);
    }

    /**
     * A failed staff send must not silently disable the assistant.
     */
    public function test_a_failed_staff_send_does_not_enable_takeover(): void
    {
        $this->actingAs($this->makeUser(false));
        $lead = $this->makeLead(['last_inbound_at' => now()->subHour()]);

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendText')->once()->andThrow(new \RuntimeException('Graph API is down'));
        $this->swap(MetaWhatsAppService::class, $whatsApp);

        $this->post(route('admin.leads.send', $lead), ['message' => 'Hello from HD'])
            ->assertSessionHasErrors('message');

        $this->assertFalse($lead->fresh()->human_takeover);
        $this->assertNull($lead->fresh()->assigned_to);
    }

    /**
     * The stored context must be visible to staff: the extra service the
     * customer mentioned and a later advert click, without either of them
     * replacing the campaign route.
     */
    public function test_lead_page_shows_the_extra_service_and_later_click(): void
    {
        $this->actingAs($this->makeUser(false));

        $lead = $this->makeLead([
            'intent' => 'change_accountant',
            'intent_source' => 'ad_mapping',
            'ad_id' => '111',
            'original_ad_id' => '111',
            'latest_touch_ad_id' => '222',
            'qualification_data' => ['mentioned_intent' => 'tax_vat_payroll'],
        ]);

        $this->get(route('admin.leads.show', $lead))
            ->assertOk()
            ->assertSee('Change accountant')
            ->assertSee('Tax / VAT / Payroll')
            ->assertSee('Later click');
    }

    /**
     * Marking a lead as finished by hand means the assistant is done: the next
     * reply must not send the closing text or re-alert the team.
     */
    public function test_manually_finished_lead_does_not_resend_the_closing_message(): void
    {
        $lead = $this->makeLead(['conversation_step' => 'person_type']);

        app(LeadController::class)->update(new Request(['qualification_status' => 'GENUINE']), $lead);

        $lead->refresh();
        $this->assertNotNull($lead->automation_completed_at);
        $this->assertNotNull($lead->qualified_at);
        $this->assertSame('complete', $lead->conversation_step);

        // A later reply is a follow-up alert, never a second closing message.
        $message = $this->makeInbound($lead, 'Thanks, one more question');
        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldNotReceive('sendText');
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notifyFollowUp')->once();

        $this->conversation($whatsApp, $alerts)->process($message);

        $this->assertSame('GENUINE', $lead->fresh()->qualification_status);
    }

    // ------------------------------------------------------------------
    // Retention, scheduling and provider limits
    // ------------------------------------------------------------------

    public function test_prune_command_only_deletes_expired_terminal_leads(): void
    {
        $oldRejected = $this->makeLead(['qualification_status' => 'REJECTED']);
        $oldNonSales = $this->makeLead(['qualification_status' => 'NON_SALES']);
        $oldGenuine = $this->makeLead(['qualification_status' => 'GENUINE']);
        $recentRejected = $this->makeLead(['qualification_status' => 'REJECTED']);

        $old = now()->subDays(60);
        foreach ([$oldRejected, $oldNonSales, $oldGenuine] as $lead) {
            DB::table('leads')->where('id', $lead->id)->update(['updated_at' => $old]);
        }

        $this->artisan('leads:prune', ['--days' => 30])->assertExitCode(0);

        $this->assertDatabaseMissing('leads', ['id' => $oldRejected->id]);
        $this->assertDatabaseMissing('leads', ['id' => $oldNonSales->id]);
        $this->assertDatabaseHas('leads', ['id' => $oldGenuine->id]);
        $this->assertDatabaseHas('leads', ['id' => $recentRejected->id]);
    }

    public function test_prune_is_registered_in_the_scheduler(): void
    {
        Artisan::call('schedule:list');
        $this->assertStringContainsString('leads:prune', Artisan::output());
    }

    /**
     * Review P0-1: lead_messages.lead_id is ON DELETE CASCADE, so removing an
     * expired non-sales lead also removes its conversation. The documentation
     * must say this, and this test proves the actual database behaviour.
     */
    public function test_prune_deleting_an_expired_lead_also_deletes_its_messages(): void
    {
        $lead = $this->makeLead(['qualification_status' => 'NON_SALES']);
        $first = $this->makeInbound($lead, 'We sell SEO services');
        $second = $this->makeInbound($lead, 'Still interested in selling you SEO');
        DB::table('leads')->where('id', $lead->id)->update(['updated_at' => now()->subDays(400)]);

        $this->artisan('leads:prune', ['--days' => 365])->assertExitCode(0);

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
        $this->assertDatabaseMissing('lead_messages', ['id' => $first->id]);
        $this->assertDatabaseMissing('lead_messages', ['id' => $second->id]);
    }

    /**
     * Review P0-2: an archive holds personal data, so it needs its own clock.
     */
    public function test_prune_removes_expired_lead_archives(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $disk->put('lead-archives/leads-old.json', '{"lead_count":1}');
        $disk->put('lead-archives/leads-new.json', '{"lead_count":1}');
        touch($disk->path('lead-archives/leads-old.json'), now()->subDays(400)->getTimestamp());

        $this->artisan('leads:prune', ['--days' => 365, '--archive-days' => 365])->assertExitCode(0);

        $disk->assertMissing('lead-archives/leads-old.json');
        $disk->assertExists('lead-archives/leads-new.json');
    }

    public function test_prune_clears_old_raw_payloads_but_keeps_conversation_rows(): void
    {
        $lead = $this->makeLead();
        $message = $this->makeInbound($lead, 'Hello');
        $message->forceFill(['raw_payload' => ['text' => ['body' => 'Hello']]])->save();
        DB::table('lead_messages')->where('id', $message->id)->update(['created_at' => now()->subDays(120)]);

        $this->artisan('leads:prune', ['--days' => 365, '--payload-days' => 90])->assertExitCode(0);

        $message->refresh();
        $this->assertNull($message->raw_payload);
        $this->assertSame('Hello', $message->body);
    }

    public function test_normal_admin_cannot_purge_all_leads(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->makeUser(false));

        $this->expectException(AuthorizationException::class);

        try {
            app(LeadController::class)->clearAll(new Request(['confirmation' => 'DELETE']));
        } finally {
            $this->assertDatabaseHas('leads', ['id' => $lead->id]);
        }
    }

    public function test_super_admin_purge_archives_before_deleting(): void
    {
        Storage::fake('local');

        $first = $this->makeLead();
        $this->makeInbound($first, 'Hello');
        $second = $this->makeCompletedLead();
        $this->makeInbound($second, 'Following up');

        $this->actingAs($this->makeUser(true));

        $response = app(LeadController::class)->clearAll(new Request(['confirmation' => 'DELETE']));

        $this->assertSame(route('admin.leads.index'), $response->getTargetUrl());
        $this->assertSame(0, Lead::whereIn('id', [$first->id, $second->id])->count());
        $this->assertSame(0, LeadMessage::whereIn('lead_id', [$first->id, $second->id])->count());

        $archives = Storage::disk('local')->allFiles('lead-archives');
        $this->assertCount(1, $archives);

        $archive = json_decode(Storage::disk('local')->get($archives[0]), true);
        $archivedIds = array_column($archive['leads'], 'id');
        $this->assertContains($first->id, $archivedIds);
        $this->assertContains($second->id, $archivedIds);

        $archivedFirst = collect($archive['leads'])->firstWhere('id', $first->id);
        $this->assertSame('Hello', $archivedFirst['messages'][0]['body']);
    }

    public function test_purge_requires_typed_confirmation(): void
    {
        $lead = $this->makeLead();
        $this->makeInbound($lead, 'Hello');
        $this->actingAs($this->makeUser(true));

        try {
            app(LeadController::class)->clearAll(new Request(['confirmation' => 'yes']));
            $this->fail('An unconfirmed delete must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('confirmation', $e->errors());
        }

        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_alert_failure_is_recorded_on_the_lead(): void
    {
        config(['lead_qualification.alert_email' => 'team@hd.example']);
        $lead = $this->makeLead(['qualification_status' => 'GENUINE']);

        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('smtp timeout'));

        $status = app(LeadAlertService::class)->notify($lead);

        $lead->refresh();
        $this->assertSame(LeadAlertService::STATUS_FAILED, $status);
        $this->assertSame(LeadAlertService::STATUS_FAILED, $lead->alert_status);
        $this->assertStringContainsString('smtp timeout', (string) $lead->alert_last_error);
        $this->assertNotNull($lead->alert_last_attempt_at);
    }

    public function test_unrecognised_free_text_asks_once_then_goes_to_review(): void
    {
        $lead = $this->makeLead([
            'intent' => 'change_accountant',
            'conversation_step' => 'person_type',
            'qualification_status' => 'QUALIFYING',
        ]);
        // The bot has already asked a question, so an unclear answer counts.
        LeadMessage::create([
            'lead_id' => $lead->id,
            'direction' => 'outbound',
            'sender_type' => 'bot',
            'message_type' => 'interactive_button',
            'body' => 'And which best describes you?',
            'delivery_status' => 'sent',
        ]);

        $whatsApp = Mockery::mock(MetaWhatsAppService::class);
        $whatsApp->shouldReceive('sendButtons')->once();   // one clarifying question
        $whatsApp->shouldReceive('sendText')->once();      // REVIEW closing message
        $alerts = Mockery::mock(LeadAlertService::class);
        $alerts->shouldReceive('notify')->once();
        $attribution = Mockery::mock(MetaAdAttributionService::class);
        $attribution->shouldReceive('enrich');

        $service = new LeadConversationService(
            $whatsApp,
            app(LeadInterpreter::class),
            app(LeadQualificationService::class),
            $alerts,
            $attribution
        );

        $service->process($this->makeInbound($lead, 'umm not sure'));

        $lead->refresh();
        $this->assertSame(1, (int) $lead->unclear_replies);
        $this->assertSame('QUALIFYING', $lead->qualification_status);
        $this->assertSame('person_type', $lead->conversation_step);

        $service->process($this->makeInbound($lead, 'no idea really'));

        $lead->refresh();
        $this->assertSame('REVIEW', $lead->qualification_status);
        $this->assertNotNull($lead->automation_completed_at);
    }

    public function test_health_page_shows_optional_items_without_a_failure(): void
    {
        config([
            'lead_qualification.alert_email' => 'team@hd.example',
            'lead_qualification.alert_phone' => null,
            'lead_qualification.alert_sms_enabled' => false,
            'meta_whatsapp.reengagement_template' => null,
        ]);

        $this->actingAs($this->makeUser(false));

        $response = $this->get('/admin/leads/health');

        $response->assertOk();
        $response->assertSee('Re-engagement template');
        $response->assertSee('Optional');
        $response->assertSee('Green means the configuration is present');

        // Review 4.2: the ads token is reported separately from the WhatsApp
        // token instead of being assumed to carry Marketing API permissions.
        $response->assertSee('META_ADS_ACCESS_TOKEN');

        // Review 4.2: the page may only promise scheduled pruning when
        // leads:prune is really registered in App\Console\Kernel.
        $response->assertSeeInOrder(['Retention prune scheduled', 'Ready'], false);
    }

    /**
     * Review P1-1: the SMS row must use the same requirements as sendSms(),
     * so a configured phone number cannot look Ready while Twilio credentials
     * are missing.
     */
    public function test_health_sms_row_requires_the_twilio_credentials_used_to_send(): void
    {
        $this->actingAs($this->makeUser(false));

        // The getways row is a fallback credential source; clear it in this
        // transaction so the assertion is deterministic.
        DB::table('getways')->where('name', 'Twilio')->delete();

        config([
            'lead_qualification.alert_email' => 'team@hd.example',
            'lead_qualification.alert_sms_enabled' => true,
            'lead_qualification.alert_phone' => '+447700900123',
            'lead_qualification.twilio_from' => '+441234567890',
            'lead_qualification.twilio_sid' => null,
            'lead_qualification.twilio_token' => null,
        ]);

        $this->get('/admin/leads/health')
            ->assertOk()
            ->assertSee('Missing: TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN.');

        config([
            'lead_qualification.twilio_sid' => 'ACxxx',
            'lead_qualification.twilio_token' => 'token',
        ]);

        $this->get('/admin/leads/health')
            ->assertOk()
            ->assertSee('Phone, sender and Twilio credentials are all present.');
    }

    public function test_whatsapp_option_labels_fit_meta_limits(): void
    {
        foreach (['intent_options', 'timeframe_options'] as $key) {
            foreach (config("lead_qualification.{$key}") as $label) {
                $this->assertLessThanOrEqual(24, mb_strlen($label), "{$key} label is too long: {$label}");
            }
        }

        foreach (['person_options', 'accountant_options'] as $key) {
            foreach (config("lead_qualification.{$key}") as $label) {
                $this->assertLessThanOrEqual(20, mb_strlen($label), "{$key} label is too long: {$label}");
            }
        }
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    private function makeUser(bool $superAdmin): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'lead-test-' . (++self::$seq) . '-' . substr((string) microtime(true), -6) . '@example.test',
            'password' => bcrypt('secret'),
            'type' => 1, // 1 => admin
            'super_admin' => $superAdmin,
        ]);
    }

    private function phone(): string
    {
        return '4477009' . str_pad((string) (++self::$seq), 4, '0', STR_PAD_LEFT);
    }

    private function wamid(): string
    {
        return 'wamid.TEST.' . (++self::$seq) . '.' . substr((string) microtime(true), -6);
    }

    private function makeLead(array $overrides = []): Lead
    {
        $phone = $overrides['whatsapp_id'] ?? $this->phone();

        return Lead::create(array_merge([
            'name' => 'Test Prospect',
            'whatsapp_id' => $phone,
            'phone' => $phone,
            'source' => 'whatsapp',
            'qualification_status' => 'NEW',
            'sales_status' => 'NEW',
            'conversation_step' => 'intent',
        ], $overrides));
    }

    private function makeCompletedLead(array $overrides = []): Lead
    {
        return $this->makeLead(array_merge([
            'qualification_status' => 'GENUINE',
            'conversation_step' => 'complete',
            'automation_completed_at' => now(),
        ], $overrides));
    }

    private function conversation(?MetaWhatsAppService $whatsApp = null, ?LeadAlertService $alerts = null): LeadConversationService
    {
        return new LeadConversationService(
            $whatsApp ?: Mockery::mock(MetaWhatsAppService::class),
            app(LeadInterpreter::class),
            app(LeadQualificationService::class),
            $alerts ?: Mockery::mock(LeadAlertService::class),
            app(MetaAdAttributionService::class)
        );
    }

    private function makeInbound(Lead $lead, string $body): LeadMessage
    {
        return LeadMessage::create([
            'lead_id' => $lead->id,
            'provider_message_id' => $this->wamid(),
            'direction' => 'inbound',
            'sender_type' => 'prospect',
            'message_type' => 'text',
            'body' => $body,
            'delivery_status' => 'received',
            'provider_timestamp' => now(),
        ]);
    }

    private function payload(string $from, string $messageId, array $referral = [], string $text = 'Hello'): array
    {
        $message = [
            'from' => $from,
            'id' => $messageId,
            'timestamp' => (string) now()->timestamp,
            'type' => 'text',
            'text' => ['body' => $text],
        ];
        if (!empty($referral)) {
            $message['referral'] = $referral;
        }

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['phone_number_id' => config('meta_whatsapp.phone_number_id')],
                        'contacts' => [[
                            'profile' => ['name' => 'Test Prospect'],
                            'wa_id' => $from,
                        ]],
                        'messages' => [$message],
                    ],
                ]],
            ]],
        ];
    }

    private function signedPost(array $payload, ?string $signature = null)
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature ??= 'sha256=' . hash_hmac('sha256', $raw, config('meta_whatsapp.app_secret'));

        return $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $raw);
    }
}
