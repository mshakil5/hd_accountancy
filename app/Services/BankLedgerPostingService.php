<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Posts approved bank reconciliations to the accounting ledger.
 *
 * Reconciliation approval is the single source of truth for bank-sourced
 * entries, so that P&L / Trial Balance / Balance Sheet reflect them without
 * duplication:
 *
 *  - Matched entries: the linked receipt already posts its own journal, so the
 *    bank side links only and posts nothing.
 *  - Categorised entries: post the entry amount against the chosen account head.
 *  - Split entries: post every allocation line that carries an account head;
 *    receipt-linked lines are skipped (the receipt posts itself).
 *  - Money out is a debit leg (type "payable"), money in a credit leg
 *    ("receivable") — the same convention the receipt posting uses.
 *
 * Idempotency: bank_entries.journal_posted_at is the guard. Approval posts once;
 * reopening reverses and clears the guard so a fresh approval may post again.
 */
class BankLedgerPostingService
{
    /**
     * Post the ledger legs for one bank entry. Safe to call repeatedly.
     *
     * @return int number of ledger rows written
     */
    public function postEntry(object $entry, int $actorId): int
    {
        if (!empty($entry->journal_posted_at)) return 0;
        if (!in_array($entry->status, ['matched', 'categorized', 'split'], true)) return 0;

        $legs = $this->legsFor($entry);
        $type = ((float) $entry->amount < 0) ? 'payable' : 'receivable';

        $posted = 0;
        foreach ($legs as $leg) {
            $amount = round((float) $leg['amount'], 2);
            if ($amount <= 0) continue;

            Transaction::create([
                'transaction_uid' => 'TXN-' . strtoupper(Str::random(10)),
                'receipt_id'      => null,
                'bank_entry_id'   => $entry->id,
                'client_id'       => $entry->client_id,
                'entry_date'      => $entry->transaction_date,
                'account_head_id' => $leg['account_head_id'],
                'type'            => $type,
                'amount'          => $amount,
                'tax_percent'     => 0,
                'tax_amount'      => 0,
                'total_amount'    => $amount,
                'payment_method'  => 'bank',
                'parent_id'       => null,
                'created_by'      => $actorId,
            ]);
            $posted++;
        }

        // Mark posted even when nothing was written (a matched row) so the guard
        // records that this approval has been processed.
        DB::table('bank_entries')->where('id', $entry->id)
            ->update(['journal_posted_at' => now(), 'updated_at' => now()]);

        return $posted;
    }

    /**
     * Remove every ledger row this entry posted and clear the posted guard.
     * Called when an approved reconciliation is reopened for editing.
     */
    public function reverseEntry(object $entry): int
    {
        if (empty($entry->journal_posted_at)) return 0;

        $deleted = Transaction::where('bank_entry_id', $entry->id)->delete();
        DB::table('bank_entries')->where('id', $entry->id)
            ->update(['journal_posted_at' => null, 'updated_at' => now()]);

        return $deleted;
    }

    /**
     * Ledger legs for an entry, i.e. the account heads (and amounts) to post.
     *
     * @return array<int,array{account_head_id:int,amount:float}>
     */
    private function legsFor(object $entry): array
    {
        if ($entry->status === 'categorized') {
            return $entry->account_head_id
                ? [['account_head_id' => (int) $entry->account_head_id, 'amount' => abs((float) $entry->amount)]]
                : [];
        }

        if ($entry->status === 'split') {
            $legs = [];
            $lines = DB::table('bank_allocation_lines')->where('bank_entry_id', $entry->id)->get();
            foreach ($lines as $line) {
                if ($line->receipt_id) continue;       // receipt owns its own posting
                if (!$line->account_head_id) continue; // nothing to post against
                $legs[] = ['account_head_id' => (int) $line->account_head_id, 'amount' => (float) $line->amount];
            }
            return $legs;
        }

        return []; // matched => link only
    }
}
