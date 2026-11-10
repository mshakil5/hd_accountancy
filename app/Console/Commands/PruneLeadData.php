<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadMessage;
use Illuminate\Console\Command;

class PruneLeadData extends Command
{
    protected $signature = 'leads:prune {--days=} {--payload-days=}';
    protected $description = 'Delete expired rejected/non-sales leads and strip old raw Meta payloads';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('lead_qualification.retention_days'));
        $count = Lead::whereIn('qualification_status', ['REJECTED','NON_SALES'])
            ->where('updated_at', '<', now()->subDays($days))->delete();
        $this->info("Deleted {$count} expired lead records.");

        // Data minimisation: keep qualification/audit data but stop holding the
        // full Meta payload (which can contain personal data) indefinitely.
        $payloadDays = (int) ($this->option('payload-days') ?: config('lead_qualification.raw_payload_retention_days'));
        if ($payloadDays > 0) {
            $cleared = LeadMessage::query()
                ->whereNotNull('raw_payload')
                ->where('created_at', '<', now()->subDays($payloadDays))
                ->update(['raw_payload' => null]);
            $this->info("Cleared raw payloads from {$cleared} message(s) older than {$payloadDays} days.");
        }

        return self::SUCCESS;
    }
}
