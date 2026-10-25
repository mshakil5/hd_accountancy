<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LeadController;
use App\Jobs\ProcessIncomingLeadMessage;
use App\Models\Lead;
use App\Models\LeadMessage;
use App\Services\LeadAlertService;
use App\Services\LeadConversationService;
use App\Services\LeadQualificationService;
use App\Services\MetaAdAttributionService;
use App\Services\MetaWhatsAppService;
use App\Services\LeadInterpreter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
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
            || !Schema::hasColumn('leads', 'automation_completed_at')) {
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

    public function test_prune_is_not_registered_in_the_scheduler(): void
    {
        Artisan::call('schedule:list');
        $this->assertStringNotContainsString('leads:prune', Artisan::output());
    }

    public function test_admin_can_delete_all_leads_and_their_messages(): void
    {
        $first = $this->makeLead();
        $this->makeInbound($first, 'Hello');
        $second = $this->makeCompletedLead();
        $this->makeInbound($second, 'Following up');

        $response = app(LeadController::class)->clearAll(new Request(['confirmation' => 'DELETE']));

        $this->assertSame(route('admin.leads.index'), $response->getTargetUrl());
        $this->assertSame(0, Lead::whereIn('id', [$first->id, $second->id])->count());
        $this->assertSame(0, LeadMessage::whereIn('lead_id', [$first->id, $second->id])->count());
    }

    public function test_deleting_all_leads_requires_typed_confirmation(): void
    {
        $lead = $this->makeLead();
        $this->makeInbound($lead, 'Hello');

        try {
            app(LeadController::class)->clearAll(new Request(['confirmation' => 'yes']));
            $this->fail('An unconfirmed delete must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('confirmation', $e->errors());
        }

        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
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
