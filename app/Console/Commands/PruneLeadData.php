<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneLeadData extends Command
{
    protected $signature = 'leads:prune {--days=} {--payload-days=} {--archive-days=}';
    protected $description = 'Delete expired rejected/non-sales leads, strip old raw Meta payloads and retire old archives';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('lead_qualification.retention_days'));
        $count = Lead::whereIn('qualification_status', ['REJECTED','NON_SALES'])
            ->where('updated_at', '<', now()->subDays($days))->delete();
        $this->info("Deleted {$count} expired lead record(s) and their messages (lead_messages cascades on delete).");

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

        // "Archive & clear" leaves the personal data on disk, so archives get
        // their own retention clock instead of accumulating forever.
        $archiveDays = (int) ($this->option('archive-days') ?: config('lead_qualification.archive_retention_days'));
        if ($archiveDays > 0) {
            $disk = Storage::disk('local');
            $cutoff = now()->subDays($archiveDays)->getTimestamp();
            $removed = 0;
            foreach ($disk->files('lead-archives') as $file) {
                if ($disk->lastModified($file) < $cutoff) {
                    $disk->delete($file);
                    $removed++;
                }
            }
            if ($removed > 0) {
                $this->info("Deleted {$removed} lead archive(s) older than {$archiveDays} days.");
            }
        }

        return self::SUCCESS;
    }
}
