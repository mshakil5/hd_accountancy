<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\AccountHead;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $transfer = ($entry->transfer_type ?? null) === 'internal_transfer';
        if (!$transfer && !in_array($entry->status, ['matched', 'categorized', 'split'], true)) return 0;

        $this->assertReadyForApproval($entry);
        $receiptPosted = $transfer ? 0 : $this->postLinkedReceipts($entry, $actorId);
        $legs = $this->legsFor($entry);
        $type = ((float) $entry->amount < 0) ? 'payable' : 'receivable';

        $posted = $receiptPosted;
        foreach ($legs as $leg) {
            $amount = round((float) $leg['amount'], 2);
            $taxAmount = round((float) ($leg['tax_amount'] ?? 0), 2);
            if ($amount <= 0 && $taxAmount <= 0) continue;

            Transaction::create([
                'transaction_uid' => 'TXN-' . strtoupper(Str::random(10)),
                'receipt_id'      => null,
                'bank_entry_id'   => $entry->id,
                'client_id'       => $entry->client_id,
                'entry_date'      => $entry->transaction_date,
                'account_head_id' => $leg['account_head_id'],
                'type'            => $type,
                'amount'          => $amount,
                'tax_percent'     => (float) ($leg['tax_percent'] ?? 0),
                'tax_amount'      => $taxAmount,
                'vat_amount'      => (float) ($leg['vat_amount'] ?? 0),
                'total_amount'    => $amount + $taxAmount,
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
     * Reject accounting approval when a linked receipt has no recognised journal.
     * Financial reports must never indicate an approved match that has not posted.
     * This is a guard, NOT the forthcoming receipt-to-bank-led posting conversion.
     */
    public function assertReadyForApproval(object $entry): void
    {
        $transfer = ($entry->transfer_type ?? null) === 'internal_transfer';
        if (!$transfer && !in_array($entry->status, ['matched', 'categorized', 'split'], true)) {
            throw ValidationException::withMessages(['reconciliation' => 'Match, categorise or split this transaction first.']);
        }
        if (round(abs((float) $entry->amount), 2) <= 0) {
            throw ValidationException::withMessages(['reconciliation' => 'Zero-amount bank entries cannot be approved.']);
        }
        if ($transfer) {
            if (empty($entry->linked_bank_entry_id)) {
                throw ValidationException::withMessages(['reconciliation' => 'Internal transfer must be linked to its counterparty bank entry.']);
            }
            $counter = DB::table('bank_entries')->where('id', (int) $entry->linked_bank_entry_id)
                ->where('client_id', $entry->client_id)->first();
            abort_unless($counter, 404, 'Linked bank entry not found.');
            if (!empty($counter->transfer_type) && $counter->transfer_type === 'internal_transfer'
                && empty($counter->linked_bank_entry_id)) {
                throw ValidationException::withMessages(['reconciliation' => 'Counterparty entry is not linked back to this transfer.']);
            }
            if (!$entry->account_head_id) {
                throw ValidationException::withMessages(['reconciliation' => 'Choose the bank account head for this side of the transfer.']);
            }
            $this->assertAccountHeadOwned((int) $entry->account_head_id, (int) $entry->client_id);
            return;
        }
        if ($entry->status === 'matched') {
            $this->assertReceiptPosted($entry->receipt_id, $entry->client_id);
        } elseif ($entry->status === 'categorized') {
            if (!$entry->account_head_id) {
                throw ValidationException::withMessages(['reconciliation' => 'Choose an account head before approval.']);
            }
            $this->assertAccountHeadOwned((int) $entry->account_head_id, (int) $entry->client_id);
        } else {
            $lines = DB::table('bank_allocation_lines')->where('bank_entry_id', $entry->id)->get();
            if ($lines->count() < 2) {
                throw ValidationException::withMessages(['reconciliation' => 'Split needs two or more allocation lines.']);
            }
            $sum = 0;
            foreach ($lines as $line) {
                $sum += (int) round((float) $line->amount * 100);
                if (!$line->receipt_id && !$line->account_head_id) {
                    throw ValidationException::withMessages(['reconciliation' => 'Every split line needs a receipt or an account head.']);
                }
                if ($line->account_head_id) $this->assertAccountHeadOwned((int) $line->account_head_id, (int) $entry->client_id);
                if ($line->receipt_id) $this->assertReceiptPosted($line->receipt_id, $entry->client_id);
                if ($line->account_head_id && isset($line->tax_percent) && ($line->tax_percent < 0 || $line->tax_percent > 100)) {
                    throw ValidationException::withMessages(['reconciliation' => 'Tax percent must be between 0 and 100.']);
                }
            }
            if ($sum !== (int) round(abs((float) $entry->amount) * 100)) {
                throw ValidationException::withMessages(['reconciliation' => 'Split allocations do not equal the bank amount.']);
            }
        }
    }

    private function assertAccountHeadOwned(int $headId, int $clientId): void
    {
        $credentialId = DB::table('clients')->where('id', $clientId)->value('client_credential_id');
        if (!$credentialId || !DB::table('account_heads')->where('id', $headId)
            ->where('client_credential_id', $credentialId)->exists()) {
            throw ValidationException::withMessages(['reconciliation' => 'Allocation account head does not belong to this client.']);
        }
        if (!DB::table('account_heads')->where('id', $headId)->whereNotNull('account_type_id')->exists()) {
            throw ValidationException::withMessages(['reconciliation' => 'Assign an Account Type to this account head (Chart of Accounts) before using it.']);
        }
    }

    private function assertReceiptPosted(?int $receiptId, int $clientId): void
    {
        if (!$receiptId || !DB::table('receipts')->where('id', $receiptId)->where('client_id', $clientId)->exists()) {
            throw ValidationException::withMessages(['reconciliation' => 'Linked receipt is missing or belongs to another client.']);
        }
        if (!Transaction::where('receipt_id', $receiptId)->exists()) {
            throw ValidationException::withMessages(['reconciliation' => 'This receipt has not yet produced an accounting entry. Review receipt approval before bank approval.']);
            $receipt = Receipt::with('detail')->find($receiptId);
            if (!$receipt || !in_array($receipt->status, ['ready','archived'], true) || !$receipt->detail || !$receipt->detail->account_head_id || (float)$receipt->detail->total_amount <= 0) {
                throw ValidationException::withMessages(['reconciliation' => 'Validate the receipt details and account head before reconciling.']);
            }
            $this->assertAccountHeadOwned((int)$receipt->detail->account_head_id, $clientId);
        }
    }

    /** Post receipt recognition on bank approval only when no historical journal exists. */
    private function postLinkedReceipts(object $entry, int $actorId): int
    {
        return 0;
        $ids = $entry->status === 'matched' ? [(int)$entry->receipt_id] : [];
        if ($entry->status === 'split') {
            $ids = DB::table('bank_allocation_lines')->where('bank_entry_id',$entry->id)
                ->whereNotNull('receipt_id')->pluck('receipt_id')->map(fn($id)=>(int)$id)->all();
        }
        $count=0;
        foreach(array_unique($ids) as $id) {
            if (Transaction::where('receipt_id',$id)->exists()) continue;
            $receipt=Receipt::with('detail')->where('client_id',$entry->client_id)->findOrFail($id);
            $detail=$receipt->detail;
            $head=AccountHead::with('accountType')->findOrFail($detail->account_head_id);
            if (!$head->accountType) {
                throw ValidationException::withMessages(['reconciliation' => 'Assign an Account Type to account head "'.$head->name.'" before posting.']);
            }
            $type=($head->accountType->normal_balance === 'debit') ? 'payable' : 'receivable';
            $net=(float)($detail->net_amount ?? 0);
            $tax=(float)($detail->tax_amount ?? 0);
            $vat=(float)($detail->vat_amount ?? 0);
            $gross=(float)$detail->total_amount;
            // Existing reports use the same transaction conventions as receipt validation.
            $base=Transaction::create([
                'transaction_uid'=>'TXN-'.strtoupper(Str::random(10)),
                'receipt_id'=>$id,'bank_entry_id'=>$entry->id,
                'client_id'=>$entry->client_id,'entry_date'=>$entry->transaction_date,
                'account_head_id'=>$head->id,'type'=>$type,
                'amount'=>$net,'tax_percent'=>0,'tax_amount'=>$tax,'vat_amount'=>$vat,
                'total_amount'=>$gross,'payment_method'=>'bank','parent_id'=>null,'created_by'=>$actorId,
            ]);
            $count++;
            if ((bool)$detail->paid) {
                Transaction::create([
                    'transaction_uid'=>'TXN-'.strtoupper(Str::random(10)),
                    'receipt_id'=>$id,'bank_entry_id'=>$entry->id,'client_id'=>$entry->client_id,
                    'entry_date'=>$entry->transaction_date,'account_head_id'=>$head->id,
                    'type'=>$type==='payable'?'paid':'received',
                    'amount'=>$gross,'tax_percent'=>0,'tax_amount'=>0,'vat_amount'=>0,
                    'total_amount'=>$gross,'payment_method'=>'bank','parent_id'=>$base->id,'created_by'=>$actorId,
                ]);
                $count++;
            }
        }
        return $count;
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

        // If this was one side of an internal transfer, reverse the counterparty too.
        if (!empty($entry->linked_bank_entry_id) && ($entry->transfer_type ?? null) === 'internal_transfer') {
            $counter = DB::table('bank_entries')->where('id', (int) $entry->linked_bank_entry_id)->first();
            if ($counter && !empty($counter->journal_posted_at)) {
                $this->reverseEntry((object) $counter);
            }
        }

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
                $legs[] = [
                    'account_head_id' => (int) $line->account_head_id,
                    'amount'          => (float) $line->amount,
                    'tax_percent'     => (float) ($line->tax_percent ?? 0),
                    'tax_amount'      => (float) ($line->tax_amount ?? 0),
                    'vat_amount'      => (float) ($line->vat_amount ?? 0),
                ];
            }
            return $legs;
        }

        return []; // matched => link only
    }
}
