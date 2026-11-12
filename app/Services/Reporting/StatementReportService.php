<?php

namespace App\Services\Reporting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Statement-based reporting.
 *
 * The bank statement line is the source of truth: a report row exists only
 * where a bank statement line is reconciled to a receipt. Categorized-only
 * lines, internal transfers and unreconciled lines never appear.
 *
 * A line can be reconciled in two shapes:
 *  - a plain full match  -> bank_entries.receipt_id + bank_entries.account_head_id
 *  - a partial / split   -> bank_allocation_lines rows carrying receipt_id
 *
 * Both are expanded into one row per receipt allocation, so a receipt used
 * across several bank lines is counted once per line, never twice.
 */
class StatementReportService
{
    /** Money out is a negative statement amount. */
    private function direction($amount): int
    {
        return ((float) $amount) < 0 ? -1 : 1;
    }

    /**
     * Every receipt-matched bank line in the period, one row per allocation.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(int $businessId, string $from, string $to, ?string $paymentMethod = null): Collection
    {
        $entries = DB::table('bank_entries as b')
            ->leftJoin('reconciliation_bank_accounts as ba', 'ba.id', '=', 'b.bank_account_id')
            ->where('b.client_id', $businessId)
            ->where('b.reconciliation_status', 'approved')
            ->whereBetween('b.transaction_date', [$from, $to])
            ->where(function ($q) {
                $q->whereNotNull('b.receipt_id')
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('bank_allocation_lines as l')
                            ->whereColumn('l.bank_entry_id', 'b.id')
                            ->whereNotNull('l.receipt_id');
                    });
            })
            ->where(function ($q) {
                $q->whereNull('b.transfer_type')->orWhere('b.transfer_type', '!=', 'internal_transfer');
            })
            ->select([
                'b.id', 'b.transaction_date', 'b.description', 'b.reference', 'b.amount',
                'b.receipt_id', 'b.account_head_id',
                'ba.bank_name', 'ba.account_label', 'ba.account_last_four',
            ])
            ->orderBy('b.transaction_date')
            ->orderBy('b.id')
            ->get();

        if ($entries->isEmpty()) {
            return collect();
        }

        $allocations = DB::table('bank_allocation_lines')
            ->whereIn('bank_entry_id', $entries->pluck('id')->all())
            ->whereNotNull('receipt_id')
            ->get()
            ->groupBy('bank_entry_id');

        $receiptIds = $entries->pluck('receipt_id')->filter()->all();
        foreach ($allocations as $group) {
            $receiptIds = array_merge($receiptIds, $group->pluck('receipt_id')->all());
        }
        $receiptIds = array_values(array_unique($receiptIds));

        $receipts = DB::table('receipts as r')
            ->leftJoin('receipt_details as d', 'd.receipt_id', '=', 'r.id')
            ->whereIn('r.id', $receiptIds)
            ->select([
                'r.id', 'r.receipt_number', 'r.supplier', 'r.status', 'r.receipt_date',
                'd.account_head_id', 'd.invoice_date', 'd.net_amount', 'd.vat_amount',
                'd.total_amount', 'd.payment_method', 'd.description',
            ])
            ->get()
            ->keyBy('id');

        $headIds = [];
        foreach ($entries as $e) {
            if ($e->account_head_id) {
                $headIds[] = $e->account_head_id;
            }
        }
        foreach ($allocations as $group) {
            foreach ($group as $line) {
                if ($line->account_head_id) {
                    $headIds[] = $line->account_head_id;
                }
            }
        }
        foreach ($receipts as $receipt) {
            if ($receipt->account_head_id) {
                $headIds[] = $receipt->account_head_id;
            }
        }

        $heads = DB::table('account_heads as h')
            ->leftJoin('account_sub_types as st', 'st.id', '=', 'h.account_sub_type_id')
            ->leftJoin('account_types as t', 't.id', '=', 'h.account_type_id')
            ->whereIn('h.id', array_values(array_unique($headIds)))
            ->select([
                'h.id', 'h.code', 'h.name', 'h.normal_balance',
                'st.name as sub_type', 't.category', 't.normal_balance as type_normal',
            ])
            ->get()
            ->keyBy('id');

        $rows = collect();

        foreach ($entries as $entry) {
            $dir = $this->direction($entry->amount);
            $lines = $allocations->get($entry->id);

            if ($lines && $lines->count()) {
                foreach ($lines as $line) {
                    $rows->push($this->makeRow(
                        $entry,
                        $line->receipt_id,
                        $line->account_head_id,
                        (float) $line->amount,
                        (float) $line->vat_amount,
                        (float) $line->tax_percent,
                        $dir,
                        $receipts,
                        $heads,
                        $line->memo
                    ));
                }
                continue;
            }

            $rows->push($this->makeRow(
                $entry,
                $entry->receipt_id,
                $entry->account_head_id,
                abs((float) $entry->amount),
                0.0,
                0.0,
                $dir,
                $receipts,
                $heads,
                null
            ));
        }

        if ($paymentMethod) {
            $rows = $rows->filter(fn ($r) => $r['payment_method'] === $paymentMethod)->values();
        }

        return $rows;
    }

    /**
     * One report row: the applied amount on a bank line, its VAT split, and
     * the signed contribution to its account head.
     */
    private function makeRow(
        $entry,
        $receiptId,
        $headId,
        float $gross,
        float $vat,
        float $taxPercent,
        int $dir,
        Collection $receipts,
        Collection $heads,
        ?string $memo
    ): array {
        $receipt = $receiptId ? $receipts->get($receiptId) : null;
        $head = $headId ? $heads->get($headId) : null;
        if (!$head && $receipt && $receipt->account_head_id) {
            $head = $heads->get($receipt->account_head_id);
        }

        // VAT: the allocation's own figure when it has one, otherwise the
        // receipt's VAT ratio, otherwise the stored tax percent.
        if ($vat <= 0 && $receipt && (float) $receipt->total_amount > 0 && (float) $receipt->vat_amount > 0) {
            $vat = round($gross * ((float) $receipt->vat_amount / (float) $receipt->total_amount), 2);
        }
        if ($vat <= 0 && $taxPercent > 0) {
            $vat = round($gross - ($gross / (1 + $taxPercent / 100)), 2);
        }
        $vat = max(0.0, min($vat, $gross));
        $net = round($gross - $vat, 2);

        // Presentation side comes from the head's CATEGORY, not its own normal
        // balance: the chart has debit-natured contra heads inside credit
        // categories (107 Sales Refund, 604 Owners Drawing) whose debit balance
        // must reduce the section, not add to it.
        $normal = match ($head->category ?? null) {
            'revenue', 'liability', 'equity' => 'credit',
            'expense', 'asset' => 'debit',
            default => $head->normal_balance ?? $head->type_normal ?? 'debit',
        };
        $sign = $normal === 'debit' ? -1 : 1;

        return [
            'bank_entry_id' => (int) $entry->id,
            'date' => $entry->transaction_date,
            'bank_account' => trim(($entry->bank_name ?? '') . ' ' . ($entry->account_label ?? '')) ?: '—',
            'description' => $entry->description,
            'reference' => $entry->reference,
            'receipt_id' => $receiptId ? (int) $receiptId : null,
            'receipt_number' => $receipt->receipt_number ?? null,
            'supplier' => $receipt->supplier ?? null,
            'invoice_date' => $receipt->invoice_date ?? null,
            'receipt_status' => $receipt->status ?? null,
            'payment_method' => $receipt->payment_method ?? 'bank',
            'head_id' => $head->id ?? null,
            'head_code' => $head->code ?? null,
            'head_name' => $head->name ?? 'Unassigned',
            'sub_type' => $head->sub_type ?? null,
            'category' => $head->category ?? null,
            'gross' => round($gross, 2),
            'vat' => round($vat, 2),
            'net' => $net,
            'signed_gross' => round($sign * $dir * $gross, 2),
            'signed_net' => round($sign * $dir * $net, 2),
            'signed_vat' => round($dir * $vat, 2),
            // The bank side of the entry: money out reduces the bank asset.
            'bank_signed' => round($dir * $gross, 2),
            'memo' => $memo,
        ];
    }

    /** Amount for the chosen basis: gross (with VAT) or net (without VAT). */
    public function amountFor(array $row, string $basis): float
    {
        return $basis === 'net' ? (float) $row['signed_net'] : (float) $row['signed_gross'];
    }

    /**
     * Profit & Loss sections in Xero order, grouped by account head.
     *
     * @return array<string, mixed>
     */
    public function profitLoss(int $businessId, string $from, string $to, ?string $paymentMethod, string $basis): array
    {
        $rows = $this->rows($businessId, $from, $to, $paymentMethod);

        $turnover = [];
        $direct = [];
        $admin = [];

        foreach ($rows as $row) {
            if (!in_array($row['category'], ['revenue', 'expense'], true)) {
                continue;
            }
            $amount = $this->amountFor($row, $basis);
            $id = $row['head_id'] ?? 0;

            if ($row['category'] === 'revenue') {
                $bucket = 'turnover';
            } elseif ($row['sub_type'] === 'Direct Expenses') {
                $bucket = 'direct';
            } else {
                $bucket = 'admin';
            }

            if ($bucket === 'turnover') {
                $target = &$turnover;
            } elseif ($bucket === 'direct') {
                $target = &$direct;
            } else {
                $target = &$admin;
            }

            if (!isset($target[$id])) {
                $target[$id] = [
                    'head_id' => $id,
                    'code' => $row['head_code'],
                    'name' => $row['head_name'],
                    'amount' => 0.0,
                    'lines' => 0,
                ];
            }
            $target[$id]['amount'] += $amount;
            $target[$id]['lines']++;
            unset($target);
        }

        $sort = function (array $heads) {
            $heads = array_values($heads);
            usort($heads, fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']));
            return $heads;
        };

        $turnover = $sort($turnover);
        $direct = $sort($direct);
        $admin = $sort($admin);

        $sum = fn (array $heads) => round(array_sum(array_column($heads, 'amount')), 2);

        $totalTurnover = $sum($turnover);
        $totalCost = $sum($direct);
        $grossProfit = round($totalTurnover - $totalCost, 2);
        $totalAdmin = $sum($admin);
        $operating = round($grossProfit - $totalAdmin, 2);

        return [
            'from' => $from,
            'to' => $to,
            'basis' => $basis,
            'sections' => [
                ['key' => 'turnover', 'title' => 'Turnover', 'heads' => $turnover, 'total' => $totalTurnover],
                ['key' => 'direct', 'title' => 'Cost of Sales', 'heads' => $direct, 'total' => $totalCost],
                ['key' => 'admin', 'title' => 'Administrative Costs', 'heads' => $admin, 'total' => $totalAdmin],
            ],
            'total_turnover' => $totalTurnover,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'total_admin' => $totalAdmin,
            'operating_profit' => $operating,
            'profit_before_tax' => $operating,
            'profit_after_tax' => $operating,
            'line_count' => $rows->count(),
        ];
    }

    /**
     * P&L with one amount column per period (current + comparatives), laid out
     * like the exported reports: bare head names, no codes, no letters.
     *
     * @param  array<int, array{label: string, from: string, to: string}>  $periods
     * @return array<string, mixed>
     */
    public function profitLossColumns(int $businessId, array $periods, ?string $paymentMethod, string $basis): array
    {
        $count = count($periods);
        $columns = [];
        $sections = [];
        $metrics = [
            'total_turnover' => array_fill(0, $count, 0.0),
            'total_cost' => array_fill(0, $count, 0.0),
            'gross_profit' => array_fill(0, $count, 0.0),
            'total_admin' => array_fill(0, $count, 0.0),
            'operating_profit' => array_fill(0, $count, 0.0),
            'profit_before_tax' => array_fill(0, $count, 0.0),
            'profit_after_tax' => array_fill(0, $count, 0.0),
        ];

        foreach ($periods as $i => $period) {
            $columns[] = $period['label'];
            $report = $this->profitLoss($businessId, $period['from'], $period['to'], $paymentMethod, $basis);

            foreach ($report['sections'] as $section) {
                $key = $section['key'];
                if (!isset($sections[$key])) {
                    $sections[$key] = [
                        'key' => $key,
                        'title' => $section['title'],
                        'heads' => [],
                        'totals' => array_fill(0, $count, 0.0),
                    ];
                }
                foreach ($section['heads'] as $head) {
                    $hkey = $head['head_id'] ?: $head['name'];
                    if (!isset($sections[$key]['heads'][$hkey])) {
                        $sections[$key]['heads'][$hkey] = [
                            'head_id' => $head['head_id'],
                            'code' => $head['code'],
                            'name' => $head['name'],
                            'amounts' => array_fill(0, $count, 0.0),
                        ];
                    }
                    $sections[$key]['heads'][$hkey]['amounts'][$i] = $head['amount'];
                }
                $sections[$key]['totals'][$i] = $section['total'];
            }

            foreach ($metrics as $metric => $_) {
                $metrics[$metric][$i] = $report[$metric];
            }
        }

        // Only heads that carry a figure in any column are printed.
        foreach ($sections as $key => $section) {
            $heads = array_values(array_filter($section['heads'], function ($head) {
                return array_sum(array_map('abs', $head['amounts'])) >= 0.005;
            }));
            usort($heads, fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']) ?: strcmp($a['name'], $b['name']));
            $sections[$key]['heads'] = $heads;
        }

        $order = ['turnover' => 0, 'direct' => 1, 'admin' => 2];
        uasort($sections, fn ($a, $b) => ($order[$a['key']] ?? 9) <=> ($order[$b['key']] ?? 9));

        return [
            'columns' => $columns,
            'periods' => $periods,
            'basis' => $basis,
            'sections' => array_values($sections),
            ...$metrics,
        ];
    }

    /**
     * Balance Sheet with one amount column per as-at date (current + prior
     * year), laid out like the exported reports.
     *
     * @param  array<int, array{label: string, as_of: string}>  $dates
     * @return array<string, mixed>
     */
    public function balanceSheetColumns(int $businessId, array $dates, ?string $paymentMethod): array
    {
        $count = count($dates);
        $columns = [];
        $sections = [];
        $metrics = [
            'total_fixed' => array_fill(0, $count, 0.0),
            'total_current' => array_fill(0, $count, 0.0),
            'total_current_liab' => array_fill(0, $count, 0.0),
            'net_current' => array_fill(0, $count, 0.0),
            'assets_less_current' => array_fill(0, $count, 0.0),
            'total_noncurrent' => array_fill(0, $count, 0.0),
            'net_assets' => array_fill(0, $count, 0.0),
            'total_capital' => array_fill(0, $count, 0.0),
            'net_profit' => array_fill(0, $count, 0.0),
            'total_capital_and_profit' => array_fill(0, $count, 0.0),
        ];

        foreach ($dates as $i => $date) {
            $columns[] = $date['label'];
            $report = $this->balanceSheet($businessId, $date['as_of'], $paymentMethod);

            foreach ($report['sections'] as $section) {
                $key = $section['key'];
                if (!isset($sections[$key])) {
                    $sections[$key] = [
                        'key' => $key,
                        'title' => $section['title'],
                        'heads' => [],
                        'totals' => array_fill(0, $count, 0.0),
                    ];
                }
                foreach ($section['heads'] as $head) {
                    $hkey = $head['head_id'] ?: ('bank:' . $head['name']);
                    if (!isset($sections[$key]['heads'][$hkey])) {
                        $sections[$key]['heads'][$hkey] = [
                            'head_id' => $head['head_id'],
                            'code' => $head['code'],
                            'name' => $head['name'],
                            'amounts' => array_fill(0, $count, 0.0),
                        ];
                    }
                    $sections[$key]['heads'][$hkey]['amounts'][$i] = $head['amount'];
                }
                $sections[$key]['totals'][$i] = $section['total'];
            }

            foreach ($metrics as $metric => $_) {
                $metrics[$metric][$i] = $report[$metric];
            }
        }

        foreach ($sections as $key => $section) {
            $heads = array_values(array_filter($section['heads'], function ($head) {
                return array_sum(array_map('abs', $head['amounts'])) >= 0.005;
            }));
            usort($heads, fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']) ?: strcmp($a['name'], $b['name']));
            $sections[$key]['heads'] = $heads;
        }

        $order = ['fixed' => 0, 'current' => 1, 'current_liab' => 2, 'noncurrent' => 3, 'capital' => 4];
        uasort($sections, fn ($a, $b) => ($order[$a['key']] ?? 9) <=> ($order[$b['key']] ?? 9));

        return [
            'columns' => $columns,
            'dates' => $dates,
            'sections' => array_values($sections),
            ...$metrics,
        ];
    }

    /**
     * Balance Sheet as at a date, always net of VAT: the VAT portion of every
     * matched line lands on the VAT head (513) so the sheet still balances.
     *
     * @return array<string, mixed>
     */
    public function balanceSheet(int $businessId, string $asOf, ?string $paymentMethod): array
    {
        $rows = $this->rows($businessId, '1900-01-01', $asOf, $paymentMethod);

        $buckets = [
            'Fixed Assets' => [],
            'Current Assets' => [],
            'Current Liability' => [],
            'Non-current Liability' => [],
            'Capital and Reserves' => [],
        ];

        $revenue = 0.0;
        $expenses = 0.0;
        $vatTotal = 0.0;
        $bankByAccount = [];

        foreach ($rows as $row) {
            $vatTotal += (float) $row['signed_vat'];
            $bankLabel = $row['bank_account'] ?: 'Bank';
            $bankByAccount[$bankLabel] = ($bankByAccount[$bankLabel] ?? 0) + (float) $row['bank_signed'];

            if ($row['category'] === 'revenue') {
                $revenue += (float) $row['signed_net'];
                continue;
            }
            if ($row['category'] === 'expense') {
                $expenses += (float) $row['signed_net'];
                continue;
            }

            $sub = $row['sub_type'];
            if (!isset($buckets[$sub])) {
                $sub = match ($row['category']) {
                    'asset' => 'Current Assets',
                    'liability' => 'Current Liability',
                    'equity' => 'Capital and Reserves',
                    default => null,
                };
            }
            if (!$sub) {
                continue;
            }

            $id = $row['head_id'] ?? 0;
            if (!isset($buckets[$sub][$id])) {
                $buckets[$sub][$id] = [
                    'head_id' => $id,
                    'code' => $row['head_code'],
                    'name' => $row['head_name'],
                    'amount' => 0.0,
                    'lines' => 0,
                ];
            }
            $buckets[$sub][$id]['amount'] += (float) $row['signed_net'];
            $buckets[$sub][$id]['lines']++;
        }

        // VAT collected/paid sits on the VAT liability head.
        if (abs($vatTotal) >= 0.005) {
            $vatHead = DB::table('account_heads')->where('code', '513')->first();
            $id = $vatHead->id ?? 0;
            if (!isset($buckets['Current Liability'][$id])) {
                $buckets['Current Liability'][$id] = [
                    'head_id' => $id,
                    'code' => $vatHead->code ?? '513',
                    'name' => $vatHead->name ?? 'VAT',
                    'amount' => 0.0,
                    'lines' => 0,
                ];
            }
            $buckets['Current Liability'][$id]['amount'] += round($vatTotal, 2);
        }

        // The bank side of every reconciled line is a current asset, so the
        // sheet records both halves of the entry and ties out.
        foreach ($bankByAccount as $label => $amount) {
            if (abs($amount) < 0.005) {
                continue;
            }
            $buckets['Current Assets']['bank:' . $label] = [
                'head_id' => null,
                'code' => '451',
                'name' => 'Bank — ' . $label,
                'amount' => round($amount, 2),
                'lines' => 0,
            ];
        }

        $sort = function (array $heads) {
            $heads = array_values($heads);
            usort($heads, fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']));
            return $heads;
        };
        foreach ($buckets as $key => $heads) {
            $buckets[$key] = $sort($heads);
        }

        $sum = fn (array $heads) => round(array_sum(array_column($heads, 'amount')), 2);

        $fixed = $sum($buckets['Fixed Assets']);
        $current = $sum($buckets['Current Assets']);
        $currentLiab = $sum($buckets['Current Liability']);
        $nonCurrent = $sum($buckets['Non-current Liability']);
        $capital = $sum($buckets['Capital and Reserves']);

        $netCurrent = round($current - $currentLiab, 2);
        $assetsLessCurrent = round($fixed + $netCurrent, 2);
        $netAssets = round($assetsLessCurrent - $nonCurrent, 2);
        $netProfit = round($revenue - $expenses, 2);

        return [
            'as_of' => $asOf,
            'sections' => [
                ['key' => 'fixed', 'title' => 'Fixed Assets', 'heads' => $buckets['Fixed Assets'], 'total' => $fixed],
                ['key' => 'current', 'title' => 'Current Assets', 'heads' => $buckets['Current Assets'], 'total' => $current],
                ['key' => 'current_liab', 'title' => 'Creditors: amounts falling due within one year', 'heads' => $buckets['Current Liability'], 'total' => $currentLiab],
                ['key' => 'noncurrent', 'title' => 'Creditors: amounts falling due after more than one year', 'heads' => $buckets['Non-current Liability'], 'total' => $nonCurrent],
                ['key' => 'capital', 'title' => 'Capital and Reserves', 'heads' => $buckets['Capital and Reserves'], 'total' => $capital],
            ],
            'total_fixed' => $fixed,
            'total_current' => $current,
            'total_current_liab' => $currentLiab,
            'net_current' => $netCurrent,
            'assets_less_current' => $assetsLessCurrent,
            'total_noncurrent' => $nonCurrent,
            'net_assets' => $netAssets,
            'total_capital' => $capital,
            'net_profit' => $netProfit,
            'total_capital_and_profit' => round($capital + $netProfit, 2),
            'line_count' => $rows->count(),
        ];
    }

    /**
     * Drill-down for one head: the bank transactions behind it, each carrying
     * the receipt it was matched to.
     *
     * @return array<string, mixed>
     */
    public function drill(int $businessId, string $from, string $to, ?string $paymentMethod, string $basis, int $headId): array
    {
        $rows = $this->rows($businessId, $from, $to, $paymentMethod)
            ->filter(fn ($r) => (int) ($r['head_id'] ?? 0) === $headId)
            ->values();

        $transactions = $rows->map(function ($row) use ($basis) {
            return [
                'bank_entry_id' => $row['bank_entry_id'],
                'date' => $row['date'],
                'bank_account' => $row['bank_account'],
                'description' => $row['description'],
                'reference' => $row['reference'],
                'amount' => $this->amountFor($row, $basis),
                'gross' => $row['gross'],
                'vat' => $row['vat'],
                'net' => $row['net'],
                'payment_method' => $row['payment_method'],
                'receipt' => $row['receipt_id'] ? [
                    'id' => $row['receipt_id'],
                    'number' => $row['receipt_number'],
                    'supplier' => $row['supplier'],
                    'invoice_date' => $row['invoice_date'],
                    'status' => $row['receipt_status'],
                    'applied' => $row['gross'],
                    'gross' => $row['gross'],
                    'vat' => $row['vat'],
                    'net' => $row['net'],
                    'url' => url('/admin/receipts/' . $row['receipt_id']),
                ] : null,
            ];
        })->all();

        $first = $rows->first();

        return [
            'head' => [
                'id' => $headId,
                'code' => $first['head_code'] ?? null,
                'name' => $first['head_name'] ?? null,
            ],
            'count' => count($transactions),
            'total' => round(array_sum(array_column($transactions, 'amount')), 2),
            'transactions' => $transactions,
        ];
    }
}
