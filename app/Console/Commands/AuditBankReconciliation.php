<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Read-only consistency checks; does not approve, reverse or mutate accounts. */
class AuditBankReconciliation extends Command
{
    protected $signature = 'bank:reconciliation-audit {--client= : Restrict to a business/client ID}';
    protected $description = 'Read-only audit of approved bank reconciliations and journal consistency';

    public function handle(): int
    {
        $query = DB::table('bank_entries')->where('reconciliation_status', 'approved');
        if ($this->option('client') !== null) {
            if (!ctype_digit((string) $this->option('client'))) {
                $this->error('Client ID must be numeric.');
                return 2;
            }
            $query->where('client_id', (int) $this->option('client'));
        }
        $count = 0; $issues = 0;
        foreach ($query->orderBy('id')->cursor() as $entry) {
            ++$count; $errors = [];
            if (empty($entry->journal_posted_at)) $errors[] = 'missing posting marker';
            if ((float) $entry->amount == 0.0) $errors[] = 'zero amount';
            $bankJournals = DB::table('transactions')->where('bank_entry_id', $entry->id)->get();
            if ($entry->status === 'matched') {
                if (!$entry->receipt_id || !DB::table('receipts')->where('client_id', $entry->client_id)
                    ->where('id', $entry->receipt_id)->exists()) $errors[] = 'linked receipt missing';
                // Receipts are recognised by the bank approval itself, so the
                // receipt journal is expected to carry this bank_entry_id.
                if (!DB::table('transactions')->where('receipt_id', $entry->receipt_id)
                    ->where('bank_entry_id', $entry->id)->exists()) $errors[] = 'receipt journal missing';
                if ($bankJournals->count() > 2) $errors[] = 'unexpected extra bank journals';
            } elseif ($entry->status === 'categorized') {
                if (!$entry->account_head_id) $errors[] = 'account head missing';
                if ($bankJournals->count() !== 1) $errors[] = 'expected one bank journal';
            } elseif ($entry->status === 'split') {
                $lines = DB::table('bank_allocation_lines')->where('bank_entry_id', $entry->id)->get();
                $allocated = $lines->sum(fn($l) => (int) round((float) $l->amount * 100));
                if ($lines->count() < 2 || $allocated !== (int) round(abs((float) $entry->amount) * 100)) $errors[] = 'split does not balance';
                $expectedBankLegs = $lines->filter(fn($l) => !$l->receipt_id)->count();
                if ($bankJournals->count() !== $expectedBankLegs) $errors[] = 'bank journal count differs from split allocations';
                foreach ($lines as $line) if ($line->receipt_id && !DB::table('transactions')
                    ->where('receipt_id', $line->receipt_id)->where('bank_entry_id', $entry->id)
                    ->where('client_id', $entry->client_id)->exists()) $errors[] = 'split receipt journal missing';
            } else $errors[] = 'invalid approved status';
            if ($errors) {
                ++$issues;
                $this->warn('Bank entry ' . $entry->id . ' / client ' . $entry->client_id . ': ' . implode('; ', array_unique($errors)));
            }
        }
        $this->info('Audited ' . $count . ' approved transactions; flagged ' . $issues . ' entries. No records modified.');
        return $issues ? 1 : 0;
    }
}
