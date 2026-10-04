<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    /**
     * Receipts feed the reports only once completed. Completed = anything but
     * cancelled / pending / to_review (i.e. ready + archived), matching the
     * receipt workflow's own completed count.
     */
    private const EXCLUDED_RECEIPT_STATUSES = ['cancelled', 'pending', 'to_review'];

    public function profitLoss()
    {
        $clients = ClientCredential::select('id', 'first_name', 'last_name')->latest()->get();
        return view('admin.accounting.profit_loss', compact('clients'));
    }

    public function profitLossData(Request $request)
    {
        $from          = $request->from ?? Carbon::now()->startOfMonth()->toDateString();
        $to            = $request->to   ?? Carbon::now()->endOfMonth()->toDateString();
        $credentialId  = $request->client_credential_id;
        $businessId    = $request->client_id;
        $paymentMethod = $request->payment_method;

        \Log::info('PL Request', ['from' => $from, 'to' => $to, 'cred' => $credentialId, 'biz' => $businessId]);

        $report = $this->buildProfitLoss($from, $to, $credentialId, $businessId, $paymentMethod);

        return response()->json([
            'from'          => Carbon::parse($from)->format('d M Y'),
            'to'            => Carbon::parse($to)->format('d M Y'),
            ...$report,
        ]);
    }

    /**
     * Shared P&L builder (screen + CSV/XLSX export). Returns the Excel-sheet
     * layout (refs A–E) alongside the legacy dynamic grouping.
     */
    private function buildProfitLoss($from, $to, $credentialId, $businessId, $paymentMethod)
    {
        $base = Transaction::with(['accountHead.accountType', 'receipt.detail'])
            ->whereHas('receipt', function ($q) use ($credentialId, $businessId, $from, $to) {
                $q->whereNotIn('status', self::EXCLUDED_RECEIPT_STATUSES);
                if ($businessId) {
                    $q->where('client_id', $businessId);
                } elseif ($credentialId) {
                    $q->whereHas('client', fn($q2) => $q2->where('client_credential_id', $credentialId));
                }
                $q->whereHas('detail', fn($q3) => $q3->whereBetween('invoice_date', [$from, $to]));
            })
            ->whereIn('type', ['payable', 'receivable'])
            ->whereNull('parent_id'); // first leg only, like TB/BS (child legs excluded)

        if ($paymentMethod) {
            $base->whereHas('receipt.detail', fn($q) => $q->where('payment_method', $paymentMethod));
        }

        $transactions = $base->get();

        \Log::info('PL Transactions Count', ['count' => $transactions->count()]);
        foreach ($transactions as $txn) {
            \Log::info('TXN', [
                'id'       => $txn->id,
                'type'     => $txn->type,
                'amount'   => $txn->total_amount,
                'category' => $txn->accountHead?->accountType?->category,
                'head'     => $txn->accountHead?->name,
                'method'   => $txn->receipt?->detail?->payment_method,
            ]);
        }

        $income   = [];
        $expenses = [];
        $methods  = ['cash', 'bank', 'card'];

        foreach ($transactions as $txn) {
            $accountHead = $txn->accountHead;
            if (!$accountHead) continue;
            $accountType = $accountHead->accountType;
            if (!$accountType) continue;

            $category  = $accountType->category;
            $typeName  = $accountType->name;
            $headName  = $accountHead->name;
            $headId    = $accountHead->id;
            $method    = $txn->receipt?->detail?->payment_method ?? 'cash';
            $amount    = (float) $txn->total_amount;

            $entry = ['amount' => $amount, 'method' => $method];

            if ($category === 'revenue') {
                $income[$typeName][$headName]['head_id']  = $headId;
                $income[$typeName][$headName]['code']     = $accountHead->code;
                $income[$typeName][$headName]['rows'][]   = $entry;
            } elseif ($category === 'expense') {
                $expenses[$typeName][$headName]['head_id'] = $headId;
                $expenses[$typeName][$headName]['code']    = $accountHead->code;
                $expenses[$typeName][$headName]['rows'][]  = $entry;
            }
        }

        // Every head shows, even with no postings (zero row): pre-seed all
        // active revenue/expense types, then all their in-scope active heads.
        $legacyScopeCredId = null;
        if ($businessId) {
            $legacyScopeCredId = Client::where('id', $businessId)->value('client_credential_id') ?: null;
        } elseif ($credentialId) {
            $legacyScopeCredId = $credentialId;
        }
        $legacyTypes = \App\Models\AccountType::where('is_active', 1)
            ->whereIn('category', ['revenue', 'expense'])
            ->orderBy('id')->get();
        foreach ($legacyTypes as $lt) {
            if ($lt->category === 'revenue' && !isset($income[$lt->name])) $income[$lt->name] = [];
            if ($lt->category === 'expense' && !isset($expenses[$lt->name])) $expenses[$lt->name] = [];
        }
        $legacyHeads = \App\Models\AccountHead::where('is_active', 1)
            ->whereIn('account_type_id', $legacyTypes->pluck('id'))
            ->where(function ($q) use ($legacyScopeCredId) {
                $q->whereNull('client_credential_id');
                if ($legacyScopeCredId) $q->orWhere('client_credential_id', $legacyScopeCredId);
                else $q->orWhereNotNull('client_credential_id');
            })
            ->with('accountType')->get();
        foreach ($legacyHeads as $lh) {
            if (!$lh->accountType) continue;
            if ($lh->accountType->category === 'revenue') {
                if (!isset($income[$lh->accountType->name][$lh->name])) {
                    $income[$lh->accountType->name][$lh->name] = ['head_id' => $lh->id, 'code' => $lh->code, 'rows' => []];
                }
            } elseif ($lh->accountType->category === 'expense') {
                if (!isset($expenses[$lh->accountType->name][$lh->name])) {
                    $expenses[$lh->accountType->name][$lh->name] = ['head_id' => $lh->id, 'code' => $lh->code, 'rows' => []];
                }
            }
        }

        $buildSection = function ($section) use ($methods) {
            $result = [];
            foreach ($section as $typeName => $heads) {
                $typeTotal = ['cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
                $headRows  = [];
                foreach ($heads as $headName => $data) {
                    $row = ['head_name' => $headName, 'head_id' => $data['head_id'], 'code' => $data['code'] ?? '', 'cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
                    foreach ($data['rows'] as $entry) {
                        $m = in_array($entry['method'], $methods) ? $entry['method'] : 'cash';
                        $row[$m]              += $entry['amount'];
                        $row['total']         += $entry['amount'];
                        $typeTotal[$m]        += $entry['amount'];
                        $typeTotal['total']   += $entry['amount'];
                    }
                    $headRows[] = $row;
                }
                usort($headRows, fn($a, $b) => strcmp((string) $a['code'], (string) $b['code']) ?: strcmp($a['head_name'], $b['head_name']));
                $result[] = ['type_name' => $typeName, 'heads' => $headRows, 'type_total' => $typeTotal];
            }
            return $result;
        };

        $incomeData   = $buildSection($income);
        $expenseData  = $buildSection($expenses);

        $totalIncome  = ['cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
        $totalExpense = ['cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];

        foreach ($incomeData as $section) {
            foreach (['cash', 'bank', 'card', 'total'] as $m) $totalIncome[$m] += $section['type_total'][$m];
        }
        foreach ($expenseData as $section) {
            foreach (['cash', 'bank', 'card', 'total'] as $m) $totalExpense[$m] += $section['type_total'][$m];
        }

        $netProfit = [
            'cash'  => $totalIncome['cash']  - $totalExpense['cash'],
            'bank'  => $totalIncome['bank']  - $totalExpense['bank'],
            'card'  => $totalIncome['card']  - $totalExpense['card'],
            'total' => $totalIncome['total'] - $totalExpense['total'],
        ];

        // Dynamic chart sections: rows come from the heads actually stored in the DB
        // (account types resolved by name, ordered by code) — never hardcoded codes.
        // Totals are straight sums; Cash/Bank/Card kept.
        $scopeCredId = null;
        if ($businessId) {
            $scopeCredId = Client::where('id', $businessId)->value('client_credential_id') ?: null;
        } elseif ($credentialId) {
            $scopeCredId = $credentialId;
        }
        $typeIdByName = \App\Models\AccountType::pluck('id', 'name')->all(); // name => id
        $idsOf = function (string $name) use ($typeIdByName) {
            return isset($typeIdByName[$name]) ? [$typeIdByName[$name]] : [];
        };
        $excelPerHeadId = []; // head_id => [code, head_id, head_name, type_name, cash, bank, card, total]
        foreach ($transactions as $txn) {
            $head = $txn->accountHead;
            if (!$head || !$head->accountType) continue;
            $method = $txn->receipt?->detail?->payment_method ?? 'cash';
            if (!in_array($method, $methods)) $method = 'cash';
            $amount = (float) $txn->total_amount;
            if (!isset($excelPerHeadId[$head->id])) {
                $excelPerHeadId[$head->id] = [
                    'code' => $head->code,
                    'head_id' => $head->id,
                    'head_name' => $head->name,
                    'type_id' => $head->account_type_id,
                    'type_name' => $head->accountType->name,
                    'cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0,
                ];
            }
            $excelPerHeadId[$head->id][$method] += $amount;
            $excelPerHeadId[$head->id]['total'] += $amount;
        }
        // Every active head of the section's types shows as a row (global chart plus
        // in-scope per-client heads), so labels always come from the heads table.
        $excelRowsForTypes = function (array $typeIds) use ($excelPerHeadId, $scopeCredId) {
            if (empty($typeIds)) return [];
            $heads = \App\Models\AccountHead::with('accountType')
                ->where('is_active', 1)
                ->whereIn('account_type_id', $typeIds)
                ->where(function ($q) use ($scopeCredId) {
                    $q->whereNull('client_credential_id');
                    if ($scopeCredId) $q->orWhere('client_credential_id', $scopeCredId);
                    else $q->orWhereNotNull('client_credential_id');
                })
                ->get()
                ->keyBy('id');
            // Plus any transacted head of these types (retired heads keep their history visible,
            // otherwise section totals would silently drop posted balances).
            $missing = [];
            foreach ($excelPerHeadId as $hid => $agg) {
                if (in_array($agg['type_id'], $typeIds) && !isset($heads[$hid])) $missing[] = $hid;
            }
            if ($missing) {
                foreach (\App\Models\AccountHead::with('accountType')->whereIn('id', $missing)->get() as $h) {
                    $heads[$h->id] = $h;
                }
            }
            $rows = [];
            foreach ($heads as $h) {
                $agg = $excelPerHeadId[$h->id] ?? null;
                $rows[] = [
                    'code' => $h->code,
                    'head_id' => $h->id,
                    'head_name' => $h->name . ($h->is_active ? '' : ' (inactive)'),
                    'type_name' => $h->accountType?->name ?? '',
                    'cash' => $agg['cash'] ?? 0,
                    'bank' => $agg['bank'] ?? 0,
                    'card' => $agg['card'] ?? 0,
                    'total' => $agg['total'] ?? 0,
                ];
            }
            usort($rows, fn($a, $b) => strcmp((string) $a['code'], (string) $b['code']) ?: strcmp($a['head_name'], $b['head_name']));
            return $rows;
        };
        $excelSumRows = function (array $rows) {
            $s = ['cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
            foreach ($rows as $r) {
                foreach (['cash', 'bank', 'card', 'total'] as $m) $s[$m] += $r[$m];
            }
            return $s;
        };
        $sub = function ($a, $b) {
            return ['cash' => $a['cash'] - $b['cash'], 'bank' => $a['bank'] - $b['bank'], 'card' => $a['card'] - $b['card'], 'total' => $a['total'] - $b['total']];
        };
        $typeNamesOf = function (array $ids) {
            if (empty($ids)) return '';
            $names = \App\Models\AccountType::whereIn('id', $ids)->orderBy('id')->pluck('name')->all();
            return implode(' + ', $names);
        };
        $turnoverIds = $idsOf('Turnover');
        $otherIds = $idsOf('Other Income');
        $directIds = $idsOf('Direct Expenses');
        $adminIds = $idsOf('Expenses');
        if (empty($turnoverIds) && empty($otherIds)) {
            $turnoverIds = \App\Models\AccountType::where('category', 'revenue')->pluck('id')->all();
        }
        if (empty($directIds) && empty($adminIds)) {
            $adminIds = \App\Models\AccountType::where('category', 'expense')->pluck('id')->all();
        }
        $turnoverRows = $excelRowsForTypes($turnoverIds);
        $otherRows = $excelRowsForTypes($otherIds);
        $directRows = $excelRowsForTypes($directIds);
        $adminRows = $excelRowsForTypes($adminIds);
        // A = all turnover + other-income heads. B = ALL direct heads (true sum).
        // C = A-B (Gross Profit). D = all admin heads. E = C-D (Operating Profit).
        $excelA = $excelSumRows(array_merge($turnoverRows, $otherRows));
        $excelB = $excelSumRows($directRows);
        $excelC = $sub($excelA, $excelB);
        $excelD = $excelSumRows($adminRows);
        $excelE = $sub($excelC, $excelD);
        $excel = [
            'refs' => ['A' => 'Total Turnover', 'B' => 'Total Cost of Sales', 'C' => 'Gross Profit C=(A-B)', 'D' => 'Total Administrative Costs', 'E' => 'Operating Profit E=(C-D)'],
            'section_titles' => [
                'turnover' => $typeNamesOf($turnoverIds) ?: 'Turnover',
                'other' => $typeNamesOf($otherIds) ?: 'Other Income',
                'direct' => $typeNamesOf($directIds) ?: 'Direct Expenses',
                'admin' => $typeNamesOf($adminIds) ?: 'Administrative Costs',
            ],
            'turnover_heads' => $turnoverRows,
            'other_income_heads' => $otherRows,
            'total_turnover_A' => $excelA,
            'direct_heads' => $directRows,
            'total_cost_B_excel' => $excelB,
            'total_cost_direct_all' => $excelB,
            'gross_profit_C' => $excelC,
            'admin_heads' => $adminRows,
            'total_admin_D' => $excelD,
            'operating_profit_E' => $excelE,
            'notes' => [
                'Rows follow the live chart of accounts (heads in the database), grouped by account type; totals are straight sums.',
            ],
        ];

        $businessName = 'All Businesses';
        if ($businessId) {
            $client = Client::find($businessId);
            $businessName = $client ? trim(($client->name ?? '') . ' ' . ($client->last_name ?? '')) : 'Unknown';
        } elseif ($credentialId) {
            $cred = ClientCredential::find($credentialId);
            $businessName = $cred ? trim(($cred->first_name ?? '') . ' ' . ($cred->last_name ?? '')) : 'Unknown';
        }

        \Log::info('PL Result', ['income' => $totalIncome, 'expense' => $totalExpense, 'net' => $netProfit]);

        return [
            'business_name' => $businessName,
            'income'        => $incomeData,
            'expenses'      => $expenseData,
            'total_income'  => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit'    => $netProfit,
            'excel'         => $excel,
        ];
    }

    public function getBusinesses(Request $request)
    {
        $businesses = Client::where('client_credential_id', $request->credential_id)
            ->select('id', 'name', 'last_name')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'text' => trim($c->name . ' ' . $c->last_name)]);

        return response()->json($businesses);
    }

    public function trialBalance()
    {
        $clients = ClientCredential::select('id', 'first_name', 'last_name')->latest()->get();
        return view('admin.accounting.trial_balance', compact('clients'));
    }

    public function trialBalanceData(Request $request)
    {
        $from         = $request->from ?? Carbon::now()->startOfYear()->toDateString();
        $to           = $request->to   ?? Carbon::now()->endOfYear()->toDateString();
        $credentialId = $request->client_credential_id;
        $businessId   = $request->client_id;

        \Log::info('TB Request', ['from' => $from, 'to' => $to, 'cred' => $credentialId, 'biz' => $businessId]);

        $transactions = Transaction::with(['accountHead.accountType'])
            ->whereHas('receipt', function ($q) use ($credentialId, $businessId, $from, $to) {
                $q->whereNotIn('status', self::EXCLUDED_RECEIPT_STATUSES);
                if ($businessId) {
                    $q->where('client_id', $businessId);
                } elseif ($credentialId) {
                    $q->whereHas('client', fn($q2) => $q2->where('client_credential_id', $credentialId));
                }
                $q->whereHas('detail', fn($q3) => $q3->whereBetween('invoice_date', [$from, $to]));
            })
            ->whereIn('type', ['payable', 'receivable']) // শুধু first transaction
            ->whereNull('parent_id')                     // double sure
            ->get();

        \Log::info('TB Transactions Count', ['count' => $transactions->count()]);

        foreach ($transactions as $txn) {
            \Log::info('TB TXN', [
                'id'             => $txn->id,
                'type'           => $txn->type,
                'amount'         => $txn->total_amount,
                'head'           => $txn->accountHead?->name,
                'category'       => $txn->accountHead?->accountType?->category,
                'normal_balance' => $txn->accountHead?->accountType?->normal_balance,
            ]);
        }

        $grouped     = [];
        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($transactions as $txn) {
            $head = $txn->accountHead;
            if (!$head) continue;
            $type = $head->accountType;
            if (!$type) continue;

            $headId        = $head->id;
            $normalBalance = $type->normal_balance;
            $amount        = (float) $txn->total_amount;

            // debit/credit based on normal_balance + transaction type
            // payable/paid = money going out = debit side
            // receivable/received = money coming in = credit side
            $isDebitEntry = in_array($txn->type, ['payable', 'paid']);

            if (!isset($grouped[$headId])) {
                $grouped[$headId] = [
                    'code'           => $head->code,
                    'name'           => $head->name,
                    'category'       => ucfirst($type->category),
                    'normal_balance' => $normalBalance,
                    'debit'          => 0,
                    'credit'         => 0,
                ];
            }

            if ($isDebitEntry) {
                $grouped[$headId]['debit'] += $amount;
                $totalDebit += $amount;
            } else {
                $grouped[$headId]['credit'] += $amount;
                $totalCredit += $amount;
            }
        }

        \Log::info('TB Grouped', ['rows' => count($grouped), 'total_debit' => $totalDebit, 'total_credit' => $totalCredit]);

        // Every head shows, even with no postings (zero row) — like P&L.
        $tbScopeCredId = null;
        if ($businessId) {
            $tbScopeCredId = Client::where('id', $businessId)->value('client_credential_id') ?: null;
        } elseif ($credentialId) {
            $tbScopeCredId = $credentialId;
        }
        $tbHeads = \App\Models\AccountHead::with('accountType')
            ->where('is_active', 1)
            ->where(function ($q) use ($tbScopeCredId) {
                $q->whereNull('client_credential_id');
                if ($tbScopeCredId) $q->orWhere('client_credential_id', $tbScopeCredId);
                else $q->orWhereNotNull('client_credential_id');
            })
            ->get();
        foreach ($tbHeads as $h) {
            if (isset($grouped[$h->id]) || !$h->accountType) continue;
            $grouped[$h->id] = [
                'code'           => $h->code,
                'name'           => $h->name,
                'category'       => ucfirst($h->accountType->category),
                'normal_balance' => $h->accountType->normal_balance,
                'debit'          => 0,
                'credit'         => 0,
            ];
        }

        usort($grouped, fn($a, $b) => strcmp($a['code'], $b['code']));

        $businessName = 'All Businesses';
        if ($businessId) {
            $client = Client::find($businessId);
            $businessName = $client ? trim(($client->name ?? '') . ' ' . ($client->last_name ?? '')) : 'Unknown';
        } elseif ($credentialId) {
            $cred = ClientCredential::find($credentialId);
            $businessName = $cred ? trim(($cred->first_name ?? '') . ' ' . ($cred->last_name ?? '')) : 'Unknown';
        }

        return response()->json([
            'from'          => Carbon::parse($from)->format('d M Y'),
            'to'            => Carbon::parse($to)->format('d M Y'),
            'business_name' => $businessName,
            'rows'          => array_values($grouped),
            'total_debit'   => $totalDebit,
            'total_credit'  => $totalCredit,
            'balanced'      => abs($totalDebit - $totalCredit) < 0.01,
        ]);
    }

    public function balanceSheet()
    {
        $clients = ClientCredential::select('id', 'first_name', 'last_name')->latest()->get();
        return view('admin.accounting.balance_sheet', compact('clients'));
    }

    public function balanceSheetData(Request $request)
    {
        $asOf         = $request->as_of ?? Carbon::now()->toDateString();
        $credentialId = $request->client_credential_id;
        $businessId   = $request->client_id;

        \Log::info('BS Request', ['as_of' => $asOf, 'cred' => $credentialId, 'biz' => $businessId]);

        $report = $this->buildBalanceSheet($asOf, $credentialId, $businessId);

        return response()->json([
            'as_of'         => Carbon::parse($asOf)->format('d M Y'),
            ...$report,
        ]);
    }

    /**
     * Shared Balance Sheet builder (screen + CSV/XLSX export).
     */
    private function buildBalanceSheet($asOf, $credentialId, $businessId)
    {
        $transactions = Transaction::with(['accountHead.accountType'])
            ->whereHas('receipt', function ($q) use ($credentialId, $businessId, $asOf) {
                $q->whereNotIn('status', self::EXCLUDED_RECEIPT_STATUSES);
                if ($businessId) {
                    $q->where('client_id', $businessId);
                } elseif ($credentialId) {
                    $q->whereHas('client', fn($q2) => $q2->where('client_credential_id', $credentialId));
                }
                $q->whereHas('detail', fn($q3) => $q3->where('invoice_date', '<=', $asOf));
            })
            ->whereIn('type', ['payable', 'receivable'])
            ->whereNull('parent_id')
            ->get();

        \Log::info('BS Transactions Count', ['count' => $transactions->count()]);

        foreach ($transactions as $txn) {
            \Log::info('BS TXN', [
                'id'             => $txn->id,
                'type'           => $txn->type,
                'amount'         => $txn->total_amount,
                'head'           => $txn->accountHead?->name,
                'category'       => $txn->accountHead?->accountType?->category,
                'normal_balance' => $txn->accountHead?->accountType?->normal_balance,
            ]);
        }

        $assets      = [];
        $liabilities = [];
        $equity      = [];
        $revenue     = 0;
        $expenses    = 0;

        foreach ($transactions as $txn) {
            $head = $txn->accountHead;
            if (!$head) continue;
            $type = $head->accountType;
            if (!$type) continue;

            $category      = $type->category;
            $normalBalance = $type->normal_balance;
            $isDebit       = in_array($txn->type, ['payable', 'paid']);
            $amount        = (float) $txn->total_amount;

            if ($normalBalance === 'debit') {
                $balance = $isDebit ? $amount : -$amount;
            } else {
                $balance = $isDebit ? -$amount : $amount;
            }

            $headKey   = $head->id;
            $headLabel = $head->code . ' - ' . $head->name;

            if ($category === 'asset') {
                if (!isset($assets[$headKey])) $assets[$headKey] = ['head_id' => $headKey, 'code' => $head->code, 'name' => $headLabel, 'balance' => 0];
                $assets[$headKey]['balance'] += $balance;
            } elseif ($category === 'liability') {
                if (!isset($liabilities[$headKey])) $liabilities[$headKey] = ['head_id' => $headKey, 'code' => $head->code, 'name' => $headLabel, 'balance' => 0];
                $liabilities[$headKey]['balance'] += $balance;
            } elseif ($category === 'equity') {
                if (!isset($equity[$headKey])) $equity[$headKey] = ['head_id' => $headKey, 'code' => $head->code, 'name' => $headLabel, 'balance' => 0];
                $equity[$headKey]['balance'] += $balance;
            } elseif ($category === 'revenue') {
                $revenue += $balance;
            } elseif ($category === 'expense') {
                $expenses += abs($balance);
            }
        }

        $netProfit       = $revenue - $expenses;
        $totalAssets     = array_sum(array_column($assets, 'balance'));
        $totalLiab       = array_sum(array_column($liabilities, 'balance'));
        $totalEquity     = array_sum(array_column($equity, 'balance')) + $netProfit;
        $totalLiabEquity = $totalLiab + $totalEquity;

        // Every head shows in its bucket, even with no balance (zero row).
        // Revenue/expense heads flow through net profit, so only asset /
        // liability / equity heads get zero rows here.
        $legacyScopeCredId = null;
        if ($businessId) {
            $legacyScopeCredId = Client::where('id', $businessId)->value('client_credential_id') ?: null;
        } elseif ($credentialId) {
            $legacyScopeCredId = $credentialId;
        }
        $legacyHeads = \App\Models\AccountHead::with('accountType')
            ->where('is_active', 1)
            ->where(function ($q) use ($legacyScopeCredId) {
                $q->whereNull('client_credential_id');
                if ($legacyScopeCredId) $q->orWhere('client_credential_id', $legacyScopeCredId);
                else $q->orWhereNotNull('client_credential_id');
            })
            ->get();
        foreach ($legacyHeads as $lh) {
            if (!$lh->accountType) continue;
            $lcat = $lh->accountType->category;
            if (!in_array($lcat, ['asset', 'liability', 'equity'])) continue;
            $lkey = $lh->id;
            $llabel = $lh->code . ' - ' . $lh->name;
            if ($lcat === 'asset' && !isset($assets[$lkey])) {
                $assets[$lkey] = ['head_id' => $lkey, 'code' => $lh->code, 'name' => $llabel, 'balance' => 0];
            } elseif ($lcat === 'liability' && !isset($liabilities[$lkey])) {
                $liabilities[$lkey] = ['head_id' => $lkey, 'code' => $lh->code, 'name' => $llabel, 'balance' => 0];
            } elseif ($lcat === 'equity' && !isset($equity[$lkey])) {
                $equity[$lkey] = ['head_id' => $lkey, 'code' => $lh->code, 'name' => $llabel, 'balance' => 0];
            }
        }
        // Totals are unaffected by zero rows, but recompute for clarity.
        $totalAssets     = array_sum(array_column($assets, 'balance'));
        $totalLiab       = array_sum(array_column($liabilities, 'balance'));
        $totalEquity     = array_sum(array_column($equity, 'balance')) + $netProfit;
        $totalLiabEquity = $totalLiab + $totalEquity;

        // Dynamic chart sections: rows come from the heads actually stored in the DB
        // (account types resolved by name, ordered by code) — never hardcoded codes.
        // Signed per-head balances reuse the same normal-balance logic as above.
        $bsScopeCredId = null;
        if ($businessId) {
            $bsScopeCredId = Client::where('id', $businessId)->value('client_credential_id') ?: null;
        } elseif ($credentialId) {
            $bsScopeCredId = $credentialId;
        }
        $bsTypeIdByName = \App\Models\AccountType::pluck('id', 'name')->all(); // name => id
        $bsIdsOf = function (string $name) use ($bsTypeIdByName) {
            return isset($bsTypeIdByName[$name]) ? [$bsTypeIdByName[$name]] : [];
        };
        $bsTypeNamesOf = function (array $ids) {
            if (empty($ids)) return '';
            return implode(' + ', \App\Models\AccountType::whereIn('id', $ids)->orderBy('id')->pluck('name')->all());
        };
        $balByHeadId = []; // head_id => signed balance
        $bsTypeByHeadId = []; // head_id => account_type_id
        foreach ($transactions as $txn) {
            $head = $txn->accountHead;
            if (!$head || !$head->accountType) continue;
            $normalBalance = $head->accountType->normal_balance;
            $isDebit = in_array($txn->type, ['payable', 'paid']);
            $amount = (float) $txn->total_amount;
            $balance = ($normalBalance === 'debit')
                ? ($isDebit ? $amount : -$amount)
                : ($isDebit ? -$amount : $amount);
            $balByHeadId[$head->id] = ($balByHeadId[$head->id] ?? 0) + $balance;
            $bsTypeByHeadId[$head->id] = $head->account_type_id;
        }
        // Every active head of the section's types shows as a row (global chart plus
        // in-scope per-client heads), so labels always come from the heads table.
        // Transacted heads of these types are included even when retired, otherwise
        // section totals would silently drop posted balances.
        $bsRowsForTypes = function (array $typeIds) use ($balByHeadId, $bsTypeByHeadId, $bsScopeCredId) {
            if (empty($typeIds)) return [];
            $heads = \App\Models\AccountHead::with('accountType')
                ->where('is_active', 1)
                ->whereIn('account_type_id', $typeIds)
                ->where(function ($q) use ($bsScopeCredId) {
                    $q->whereNull('client_credential_id');
                    if ($bsScopeCredId) $q->orWhere('client_credential_id', $bsScopeCredId);
                    else $q->orWhereNotNull('client_credential_id');
                })
                ->get()
                ->keyBy('id');
            $missing = [];
            foreach ($balByHeadId as $hid => $bal) {
                if (in_array($bsTypeByHeadId[$hid] ?? null, $typeIds) && !isset($heads[$hid])) $missing[] = $hid;
            }
            if ($missing) {
                foreach (\App\Models\AccountHead::with('accountType')->whereIn('id', $missing)->get() as $h) {
                    $heads[$h->id] = $h;
                }
            }
            $rows = [];
            foreach ($heads as $h) {
                $rows[] = [
                    'code' => $h->code,
                    'head_id' => $h->id,
                    'name' => $h->code . ' - ' . $h->name . ($h->is_active ? '' : ' (inactive)'),
                    'balance' => $balByHeadId[$h->id] ?? 0,
                ];
            }
            usort($rows, fn($a, $b) => strcmp((string) $a['code'], (string) $b['code']) ?: strcmp($a['name'], $b['name']));
            return $rows;
        };
        $bsSumRows = fn($rows) => array_sum(array_column($rows, 'balance'));
        $fixedIds = $bsIdsOf('Fixed Assets');
        $currentIds = $bsIdsOf('Current Assets');
        $currLiabIds = $bsIdsOf('Current Liability');
        $nonCurrIds = $bsIdsOf('Non-current Liability');
        $capitalIds = $bsIdsOf('Capital and reserves');
        if (empty($fixedIds) && empty($currentIds)) {
            $fixedIds = \App\Models\AccountType::where('category', 'asset')->pluck('id')->all();
        }
        if (empty($currLiabIds) && empty($nonCurrIds)) {
            $currLiabIds = \App\Models\AccountType::where('category', 'liability')->pluck('id')->all();
        }
        if (empty($capitalIds)) {
            $capitalIds = \App\Models\AccountType::where('category', 'equity')->pluck('id')->all();
        }
        $fixedRows = $bsRowsForTypes($fixedIds);
        $currentRows = $bsRowsForTypes($currentIds);
        $currLiabRows = $bsRowsForTypes($currLiabIds);
        $nonCurrRows = $bsRowsForTypes($nonCurrIds);
        $capitalRows = $bsRowsForTypes($capitalIds);
        // A = fixed assets. B = current assets (true sum). C = current liabilities.
        // B-C = net current. A+B-C = assets less current. D = non-current.
        // Net assets = A+B-C-D. Capital = ALL capital heads.
        $excelFixedA = $bsSumRows($fixedRows);
        $excelCurrentB = $bsSumRows($currentRows);
        $excelCurrentC = $bsSumRows($currLiabRows);
        $excelNetCurrent = $excelCurrentB - $excelCurrentC;
        $excelAssetsLessCurrent = $excelFixedA + $excelNetCurrent;
        $excelNonCurrentD = $bsSumRows($nonCurrRows);
        $excelNetAssets = $excelAssetsLessCurrent - $excelNonCurrentD;
        $excelCapitalAll = $bsSumRows($capitalRows);
        $excelBs = [
            'refs' => ['A' => 'Total Fixed Assets', 'B' => 'Total Current Asset', 'C' => 'Total Current Liabilities', 'B-C' => 'Net Current Assets (Liabilities)', 'A+B-C' => 'Total Assets less Current Liabilities', 'D' => 'Total Non-Current Liabilities', 'A+B-C-D' => 'Net Assets'],
            'section_titles' => [
                'fixed' => $bsTypeNamesOf($fixedIds) ?: 'Fixed Assets',
                'current' => $bsTypeNamesOf($currentIds) ?: 'Current Assets',
                'curr_liab' => $bsTypeNamesOf($currLiabIds) ?: 'Current Liabilities',
                'noncurrent' => $bsTypeNamesOf($nonCurrIds) ?: 'Non-Current Liabilities',
                'capital' => $bsTypeNamesOf($capitalIds) ?: 'Capital and Reserves',
            ],
            'fixed_heads' => $fixedRows,
            'total_fixed_A' => $excelFixedA,
            'current_heads' => $currentRows,
            'total_current_B_excel' => $excelCurrentB,
            'current_liab_heads' => $currLiabRows,
            'total_current_C' => $excelCurrentC,
            'net_current_BC' => $excelNetCurrent,
            'total_assets_less_current_excel' => $excelAssetsLessCurrent,
            'total_assets_less_current_correct' => $excelAssetsLessCurrent,
            'noncurrent_heads' => $nonCurrRows,
            'total_noncurrent_D' => $excelNonCurrentD,
            'net_assets_excel' => $excelNetAssets,
            'capital_heads' => $capitalRows,
            'total_capital_excel' => $excelCapitalAll,
            'total_capital_all' => $excelCapitalAll,
            'notes' => [
                'Rows follow the live chart of accounts (heads in the database), grouped by account type; totals are straight sums.',
            ],
        ];

        \Log::info('BS Result', [
            'total_assets'      => $totalAssets,
            'total_liab'        => $totalLiab,
            'total_equity'      => $totalEquity,
            'net_profit'        => $netProfit,
            'revenue'           => $revenue,
            'expenses'          => $expenses,
            'total_liab_equity' => $totalLiabEquity,
        ]);

        $businessName = 'All Businesses';
        if ($businessId) {
            $client = Client::find($businessId);
            $businessName = $client ? trim(($client->name ?? '') . ' ' . ($client->last_name ?? '')) : 'Unknown';
        } elseif ($credentialId) {
            $cred = ClientCredential::find($credentialId);
            $businessName = $cred ? trim(($cred->first_name ?? '') . ' ' . ($cred->last_name ?? '')) : 'Unknown';
        }

        $sortByCode = function ($rows) {
            usort($rows, fn($a, $b) => strcmp((string) $a['code'], (string) $b['code']));
            return array_values($rows);
        };

        return [
            'business_name'     => $businessName,
            'assets'            => $sortByCode($assets),
            'liabilities'       => $sortByCode($liabilities),
            'equity'            => $sortByCode($equity),
            'net_profit'        => $netProfit,
            'total_assets'      => $totalAssets,
            'total_liabilities' => $totalLiab,
            'total_equity'      => $totalEquity,
            'total_liab_equity' => $totalLiabEquity,
            'excel'             => $excelBs,
        ];
    }

    public function profitLossExport(Request $request)
    {
        $format = strtolower($request->format ?? 'csv');
        $from = $request->from ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $request->to ?? Carbon::now()->endOfMonth()->toDateString();
        $report = $this->buildProfitLoss($from, $to, $request->client_credential_id, $request->client_id, $request->payment_method);
        $stamp = Carbon::parse($from)->format('Ymd') . '-' . Carbon::parse($to)->format('Ymd');
        $e = $report['excel'];
        $m = fn($t, $k) => round((float) ($t[$k] ?? 0), 2);
        $st = $e['section_titles'];
        $headLabel = fn($h) => trim(($h['code'] ? $h['code'] . ' - ' : '') . $h['head_name']);
        $rows = [];
        $rows[] = ['HD Accountancy — Profit & Loss (' . $report['business_name'] . ' | ' . $from . ' to ' . $to . ')', '', '', '', '', 'title'];
        $rows[] = ['Account', 'Cash', 'Bank', 'Card', 'Total', 'header'];
        $rows[] = [strtoupper($st['turnover']), '', '', '', '', 'section'];
        foreach ($e['turnover_heads'] as $h) $rows[] = [$headLabel($h), $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = [strtoupper($st['other']), '', '', '', '', 'section'];
        foreach ($e['other_income_heads'] as $h) $rows[] = [$headLabel($h), $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = ['Total Turnover (A)', $m($e['total_turnover_A'], 'cash'), $m($e['total_turnover_A'], 'bank'), $m($e['total_turnover_A'], 'card'), $m($e['total_turnover_A'], 'total'), 'total'];
        $rows[] = [strtoupper($st['direct']), '', '', '', '', 'section'];
        foreach ($e['direct_heads'] as $h) $rows[] = [$headLabel($h), $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = ['Total Cost of Sales (B)', $m($e['total_cost_B_excel'], 'cash'), $m($e['total_cost_B_excel'], 'bank'), $m($e['total_cost_B_excel'], 'card'), $m($e['total_cost_B_excel'], 'total'), 'total'];
        $rows[] = ['GROSS PROFIT (C = A-B)', $m($e['gross_profit_C'], 'cash'), $m($e['gross_profit_C'], 'bank'), $m($e['gross_profit_C'], 'card'), $m($e['gross_profit_C'], 'total'), 'grand'];
        $rows[] = [strtoupper($st['admin']), '', '', '', '', 'section'];
        foreach ($e['admin_heads'] as $h) $rows[] = [$headLabel($h), $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = ['Total Administrative Costs (D)', $m($e['total_admin_D'], 'cash'), $m($e['total_admin_D'], 'bank'), $m($e['total_admin_D'], 'card'), $m($e['total_admin_D'], 'total'), 'total'];
        $rows[] = ['OPERATING PROFIT (E = C-D)', $m($e['operating_profit_E'], 'cash'), $m($e['operating_profit_E'], 'bank'), $m($e['operating_profit_E'], 'card'), $m($e['operating_profit_E'], 'total'), 'grand'];
        $rows[] = ['Note: rows follow the live chart of accounts; totals are straight sums (A=turnover, B=direct, C=A-B, D=admin, E=C-D).', '', '', '', '', 'note'];

        if ($format === 'xlsx') {
            return $this->streamXlsx('profit-loss-' . $stamp, 'P&L', $rows);
        }
        return $this->streamCsv('profit-loss-' . $stamp . '.csv', $rows);
    }

    public function balanceSheetExport(Request $request)
    {
        $format = strtolower($request->format ?? 'csv');
        $asOf = $request->as_of ?? Carbon::now()->toDateString();
        $report = $this->buildBalanceSheet($asOf, $request->client_credential_id, $request->client_id);
        $stamp = Carbon::parse($asOf)->format('Ymd');
        $e = $report['excel'];
        $v = fn($n) => round((float) $n, 2);
        $st = $e['section_titles'];
        $rows = [];
        $rows[] = ['HD Accountancy — Balance Sheet (' . $report['business_name'] . ' | As at ' . $asOf . ')', '', 'title'];
        $rows[] = ['Account', 'Amount', 'header'];
        $rows[] = [strtoupper($st['fixed']), '', 'section'];
        foreach ($e['fixed_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Fixed Assets (A)', $v($e['total_fixed_A']), 'total'];
        $rows[] = [strtoupper($st['current']), '', 'section'];
        foreach ($e['current_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Current Assets (B)', $v($e['total_current_B_excel']), 'total'];
        $rows[] = [strtoupper($st['curr_liab']), '', 'section'];
        foreach ($e['current_liab_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Current Liabilities (C)', $v($e['total_current_C']), 'total'];
        $rows[] = ['Net Current Assets (B-C)', $v($e['net_current_BC']), 'total'];
        $rows[] = ['Total Assets less Current Liabilities (A+B-C)', $v($e['total_assets_less_current_excel']), 'grand'];
        $rows[] = [strtoupper($st['noncurrent']), '', 'section'];
        foreach ($e['noncurrent_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Non-Current Liabilities (D)', $v($e['total_noncurrent_D']), 'total'];
        $rows[] = ['Net Assets (A+B-C-D)', $v($e['net_assets_excel']), 'grand'];
        $rows[] = [strtoupper($st['capital']), '', 'section'];
        foreach ($e['capital_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Capital and Reserves', $v($e['total_capital_excel']), 'total'];
        $rows[] = ['Note: rows follow the live chart of accounts; totals are straight sums.', '', 'note'];

        if ($format === 'xlsx') {
            return $this->streamXlsx('balance-sheet-' . $stamp, 'Balance Sheet', $rows);
        }
        return $this->streamCsv('balance-sheet-' . $stamp . '.csv', $rows);
    }

    private function streamCsv($filename, array $rows)
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel
            foreach ($rows as $r) {
                fputcsv($out, array_slice($r, 0, count($r) - 1));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function streamXlsx($basename, $sheetTitle, array $rows)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));
        $money = '#,##0.00';
        $r = 1;
        foreach ($rows as $row) {
            $kind = end($row);
            $cells = array_slice($row, 0, count($row) - 1);
            foreach ($cells as $i => $val) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $r;
                $sheet->setCellValueExplicit($col, $val,
                    is_numeric($val) && $i > 0 ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($cells)) . $r;
            if (in_array($kind, ['title', 'header', 'section', 'total', 'grand'])) {
                $sheet->getStyle("A$r:$lastCol")->getFont()->setBold(true);
            }
            if ($kind === 'title') {
                $sheet->getStyle("A$r:$lastCol")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0F2544');
                $sheet->getStyle("A$r:$lastCol")->getFont()->getColor()->setRGB('FFFFFF');
            }
            if ($kind === 'header') {
                $sheet->getStyle("A$r:$lastCol")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F0F4F8');
            }
            if (in_array($kind, ['row', 'total', 'grand']) && count($cells) > 1) {
                $sheet->getStyle("B$r:$lastCol")->getNumberFormat()->setFormatCode($money);
            }
            if ($kind === 'grand') {
                $sheet->getStyle("A$r:$lastCol")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle("A$r:$lastCol")->getFill()->getStartColor()->setRGB('1A9E78');
                $sheet->getStyle("A$r:$lastCol")->getFont()->getColor()->setRGB('FFFFFF');
            }
            $r++;
        }
        foreach (range(1, count($rows[0]) - 1) as $i) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, $basename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Drill-down: transactions behind one report row.     * Accepts head_id (exact head, incl. duplicate names like Director Loan Account)
     * or code (all heads sharing that code), plus the same filters as the reports.
     * mode=bs uses as_of + first-leg only; otherwise from/to range like P&L.
     */
    public function headTransactions(Request $request)
    {
        $headId = $request->head_id;
        $code = $request->code;
        // head_ids (array or comma-separated) drills a total row: every head behind it.
        $headIdsParam = $request->input('head_ids');
        if (is_string($headIdsParam)) $headIdsParam = array_filter(explode(',', $headIdsParam));
        $credentialId = $request->client_credential_id;
        $businessId = $request->client_id;
        $paymentMethod = $request->payment_method;
        $mode = $request->mode === 'bs' ? 'bs' : 'pl';
        $from = $request->from ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $request->to ?? Carbon::now()->endOfMonth()->toDateString();
        $asOf = $request->as_of ?? Carbon::now()->toDateString();

        $headQuery = \App\Models\AccountHead::with('accountType');
        if ($headId) {
            $heads = $headQuery->where('id', $headId)->get();
        } elseif (!empty($headIdsParam)) {
            $heads = $headQuery->whereIn('id', array_map('intval', (array) $headIdsParam))->get();
        } elseif ($code) {
            $heads = $headQuery->where('code', $code)->get();
        } else {
            return response()->json(['message' => 'head_id, head_ids or code required'], 422);
        }
        if ($heads->isEmpty()) {
            return response()->json(['message' => 'Account head not found'], 404);
        }
        $headIds = $heads->pluck('id')->all();

        $query = Transaction::with(['accountHead.accountType', 'receipt.client', 'receipt.detail'])
            ->whereIn('account_head_id', $headIds)
            ->whereHas('receipt', function ($q) use ($credentialId, $businessId, $from, $to, $asOf, $mode) {
                $q->whereNotIn('status', self::EXCLUDED_RECEIPT_STATUSES);
                if ($businessId) {
                    $q->where('client_id', $businessId);
                } elseif ($credentialId) {
                    $q->whereHas('client', fn($q2) => $q2->where('client_credential_id', $credentialId));
                }
                if ($mode === 'bs') {
                    $q->whereHas('detail', fn($q3) => $q3->where('invoice_date', '<=', $asOf));
                } else {
                    $q->whereHas('detail', fn($q3) => $q3->whereBetween('invoice_date', [$from, $to]));
                }
            })
            ->whereIn('type', ['payable', 'receivable'])
            ->whereNull('parent_id'); // first leg only, matching P&L/TB/BS
        if ($paymentMethod) {
            $query->whereHas('receipt.detail', fn($q) => $q->where('payment_method', $paymentMethod));
        }

        $txns = $query->orderBy('id', 'desc')->limit(200)->get();
        $rows = $txns->map(function ($t) use ($mode) {
            $amount = (float) $t->total_amount;
            // In BS mode the breakdown total must equal the signed report-row
            // balance, so each leg carries its sign (P&L rows are plain sums).
            $signed = $amount;
            if ($mode === 'bs' && $t->accountHead?->accountType) {
                $nb = $t->accountHead->accountType->normal_balance;
                $isDebit = in_array($t->type, ['payable', 'paid']);
                $signed = ($nb === 'debit') ? ($isDebit ? $amount : -$amount) : ($isDebit ? -$amount : $amount);
            }
            return [
                'id' => $t->id,
                'date' => $t->receipt?->detail?->invoice_date,
                'receipt_id' => $t->receipt_id,
                'receipt_number' => $t->receipt?->receipt_number,
                'bill_url' => $t->receipt_id ? url('/admin/receipts/' . $t->receipt_id . '/bill') : null,
                'business' => trim(($t->receipt?->client?->name ?? '') . ' ' . ($t->receipt?->client?->last_name ?? '')),
                'head' => $t->accountHead ? ($t->accountHead->code . ' - ' . $t->accountHead->name) : '',
                'type' => $t->type,
                'payment_method' => $t->receipt?->detail?->payment_method,
                'amount' => $amount,
                'signed_amount' => $signed,
            ];
        });

        return response()->json([
            'heads' => $heads->map(fn($h) => ['id' => $h->id, 'code' => $h->code, 'name' => $h->name, 'type' => $h->accountType?->name])->values(),
            'count' => $rows->count(),
            'total' => $mode === 'bs' ? $rows->sum('signed_amount') : $rows->sum('amount'),
            'rows' => $rows,
        ]);
    }
}