<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;

class PruneLeadData extends Command
{
    protected $signature = 'leads:prune {--days=}';
    protected $description = 'Delete rejected/non-sales lead records older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('lead_qualification.retention_days'));
        $count = Lead::whereIn('qualification_status', ['REJECTED','NON_SALES'])
            ->where('updated_at', '<', now()->subDays($days))->delete();
        $this->info("Deleted {$count} expired lead records.");
        return self::SUCCESS;
    }
};
