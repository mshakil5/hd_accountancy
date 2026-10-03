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
                $q->whereNotIn('status', ['cancelled']);
                if ($businessId) {
                    $q->where('client_id', $businessId);
                } elseif ($credentialId) {
                    $q->whereHas('client', fn($q2) => $q2->where('client_credential_id', $credentialId));
                }
                $q->whereHas('detail', fn($q3) => $q3->whereBetween('invoice_date', [$from, $to]));
            })
            ->whereIn('type', ['payable', 'receivable']);

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
                $income[$typeName][$headName]['rows'][]   = $entry;
            } elseif ($category === 'expense') {
                $expenses[$typeName][$headName]['head_id'] = $headId;
                $expenses[$typeName][$headName]['rows'][]  = $entry;
            }
        }

        $buildSection = function ($section) use ($methods) {
            $result = [];
            foreach ($section as $typeName => $heads) {
                $typeTotal = ['cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
                $headRows  = [];
                foreach ($heads as $headName => $data) {
                    $row = ['head_name' => $headName, 'head_id' => $data['head_id'], 'cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
                    foreach ($data['rows'] as $entry) {
                        $m = in_array($entry['method'], $methods) ? $entry['method'] : 'cash';
                        $row[$m]              += $entry['amount'];
                        $row['total']         += $entry['amount'];
                        $typeTotal[$m]        += $entry['amount'];
                        $typeTotal['total']   += $entry['amount'];
                    }
                    $headRows[] = $row;
                }
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

        // Excel "P&L" sheet layout (Chart of account, P&L, BS.xlsx, sheet1).
        // Row order + refs replicated exactly per request (bugs included, see notes).
        // Kept alongside the existing dynamic grouping above; Cash/Bank/Card kept.
        $excelPerHead = []; // code => [code, head_name, type_name, cash, bank, card, total]
        foreach ($transactions as $txn) {
            $head = $txn->accountHead;
            if (!$head || !$head->accountType) continue;
            $code = (string) $head->code;
            $method = $txn->receipt?->detail?->payment_method ?? 'cash';
            if (!in_array($method, $methods)) $method = 'cash';
            $amount = (float) $txn->total_amount;
            if (!isset($excelPerHead[$code])) {
                $excelPerHead[$code] = [
                    'code' => $code,
                    'head_id' => $head->id,
                    'head_name' => $head->name,
                    'type_name' => $head->accountType->name,
                    'cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0,
                ];
            }
            $excelPerHead[$code][$method] += $amount;
            $excelPerHead[$code]['total'] += $amount;
        }
        // Global chart fallback so every Excel row shows its label even with zero transactions.
        $chartHeads = \App\Models\AccountHead::with('accountType')
            ->whereNull('client_credential_id')
            ->where('is_active', 1)
            ->get()
            ->keyBy(fn($h) => (string) $h->code);
        $excelRow = function ($code) use ($excelPerHead, $chartHeads) {
            if (isset($excelPerHead[$code])) return $excelPerHead[$code];
            $ch = $chartHeads[$code] ?? null;
            return ['code' => $code, 'head_id' => $ch?->id, 'head_name' => $ch?->name ?? '', 'type_name' => $ch?->accountType?->name ?? '', 'cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
        };
        $excelSum = function ($codes) use ($excelPerHead) {
            $s = ['cash' => 0, 'bank' => 0, 'card' => 0, 'total' => 0];
            foreach ($codes as $c) {
                if (!isset($excelPerHead[$c])) continue;
                foreach (['cash', 'bank', 'card', 'total'] as $m) $s[$m] += $excelPerHead[$c][$m];
            }
            return $s;
        };
        $sub = function ($a, $b) {
            return ['cash' => $a['cash'] - $b['cash'], 'bank' => $a['bank'] - $b['bank'], 'card' => $a['card'] - $b['card'], 'total' => $a['total'] - $b['total']];
        };
        $turnoverCodes = ['101','102','103','104','105','106','107'];
        $otherIncomeCodes = ['108','109'];
        $directCodes = ['201','202','203','204'];
        $adminCodes = array_map(fn($i) => (string) $i, range(301, 329));
        // A = SUM(B8:B16): all turnover + other-income heads (107 Sales Refund included as + per Excel).
        $excelA = $excelSum(array_merge($turnoverCodes, $otherIncomeCodes));
        // B = B20 only (Cost of Good Sold/Purchase 201). 202-204 displayed but excluded per Excel formula.
        $excelB = $excelSum(['201']);
        // C = (A-B): Gross Profit.
        $excelC = $sub($excelA, $excelB);
        // D = SUM(B30:B58): all 301-329 admin heads.
        $excelD = $excelSum($adminCodes);
        // E = ((B27+0)-(0+B59)): Operating Profit = C - D.
        $excelE = $sub($excelC, $excelD);
        $excelHeads = function ($codes) use ($excelRow) {
            $rows = array_map($excelRow, $codes);
            usort($rows, fn($a, $b) => strcmp($a['code'], $b['code']));
            return $rows;
        };
        $excel = [
            'refs' => ['A' => 'Total Turnover', 'B' => 'Total Cost of Sales', 'C' => 'Gross Profit C=(A-B)', 'D' => 'Total Administrative Costs', 'E' => 'Operating Profit E=(C-D)'],
            'turnover_heads' => $excelHeads($turnoverCodes),
            'other_income_heads' => $excelHeads($otherIncomeCodes),
            'total_turnover_A' => $excelA,
            'direct_heads' => $excelHeads($directCodes),
            // Excel R25 total pulls B20 only; full direct sum provided for reference.
            'total_cost_B_excel' => $excelB,
            'total_cost_direct_all' => $excelSum($directCodes),
            'gross_profit_C' => $excelC,
            'admin_heads' => $excelHeads($adminCodes),
            'total_admin_D' => $excelD,
            'operating_profit_E' => $excelE,
            'notes' => [
                'B excludes 202-204 per Excel R25 formula (B25=B20).',
                'A includes 107 Sales Refund as + per Excel SUM(B8:B16); 107 is debit-natured contra-revenue.',
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
                $q->whereNotIn('status', ['cancelled']);
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
                $q->whereNotIn('status', ['cancelled']);
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

        // Excel "BS" sheet layout (sheet2), replicated exactly per request (bugs included).
        // Signed per-head balances reuse the same normal-balance logic as above.
        $excelByCode = []; // code => balance
        $excelHeadName = []; // code => "code - name"
        $excelHeadId = []; // code => head id (first seen; disambiguates duplicate names like Director Loan Account)
        foreach ($transactions as $txn) {
            $head = $txn->accountHead;
            if (!$head || !$head->accountType) continue;
            $code = (string) $head->code;
            $normalBalance = $head->accountType->normal_balance;
            $isDebit = in_array($txn->type, ['payable', 'paid']);
            $amount = (float) $txn->total_amount;
            $balance = ($normalBalance === 'debit')
                ? ($isDebit ? $amount : -$amount)
                : ($isDebit ? -$amount : $amount);
            $excelByCode[$code] = ($excelByCode[$code] ?? 0) + $balance;
            $excelHeadName[$code] = $head->code . ' - ' . $head->name;
            $excelHeadId[$code] = $excelHeadId[$code] ?? $head->id;
        }
        $x = fn($code) => $excelByCode[$code] ?? 0;        $xSum = function ($codes) use ($x) {
            $s = 0;
            foreach ($codes as $c) $s += $x($c);
            return $s;
        };
        $fixedCodes = ['401','402','403','404','405','406','407'];
        $currentCodes = ['451','452','453','454'];
        $currLiabCodes = ['501','502','503','504','505','506','507','508','509','510','511','512','513'];
        $nonCurrCodes = ['551','552','553','554'];
        $capitalCodes = ['601','602','603','604'];
        // A = SUM(B9:B15): fixed assets.
        $excelFixedA = $xSum($fixedCodes);
        // Excel R25: Inventory row (B25) = SUM(B21:B24), i.e. Bank+Cash+Receivable+Prepayment — not 455.
        $excelInventoryRow = $xSum($currentCodes);
        // Excel R26 (B): Total Current Asset = SUM(B21:B25) = 451+452+453+454+inventoryRow → double counts.
        $excelCurrentB = $xSum($currentCodes) + $excelInventoryRow;
        // C = SUM(B30:B42): current liabilities.
        $excelCurrentC = $xSum($currLiabCodes);
        // B-C: Net Current Assets (Liabilities).
        $excelNetCurrent = $excelCurrentB - $excelCurrentC;
        // Excel R47: Total Assets less Current Liabilities = (B17+B45); B17 is the empty R17 → 0. Replicated.
        $excelAssetsLessCurrent = 0 + $excelNetCurrent;
        $excelAssetsLessCurrentCorrect = $excelFixedA + $excelNetCurrent; // reference only
        // D = SUM(B51:B54): non-current liabilities.
        $excelNonCurrentD = $xSum($nonCurrCodes);
        // Excel R57: Net Assets = (B47-(B55+0)).
        $excelNetAssets = $excelAssetsLessCurrent - $excelNonCurrentD;
        // Excel R65: Total Capital and Reserves = SUM(B61:B62): Share Capital + Retained Earning only.
        $excelCapital = $xSum(['601','602']);
        $excelCapitalAll = $xSum($capitalCodes); // reference only (incl. 603 Fund + 604 Drawing)
        // Global chart fallback so every Excel row shows its label even with zero transactions.
        $chartHeadsBs = \App\Models\AccountHead::with('accountType')
            ->whereNull('client_credential_id')
            ->where('is_active', 1)
            ->get()
            ->keyBy(fn($h) => (string) $h->code);
        $excelRows = function ($codes) use ($x, $excelHeadName, $excelHeadId, $chartHeadsBs) {
            $rows = [];
            foreach ($codes as $c) {
                $ch = $chartHeadsBs[$c] ?? null;
                $rows[] = [
                    'code' => $c,
                    'head_id' => $excelHeadId[$c] ?? $ch?->id,
                    'name' => $excelHeadName[$c] ?? ($ch ? ($ch->code . ' - ' . $ch->name) : ($c . ' - (no transactions)')),
                    'balance' => $x($c),
                ];
            }
            return $rows;
        };
        $excelBs = [
            'refs' => ['A' => 'Total Fixed Assets', 'B' => 'Total Current Asset', 'C' => 'Total Current Liabilities', 'B-C' => 'Net Current Assets (Liabilities)', 'A+B-C' => 'Total Assets less Current Liabilities', 'D' => 'Total Non-Current Liabilities', 'A+B-C-D' => 'Net Assets'],
            'fixed_heads' => $excelRows($fixedCodes),
            'total_fixed_A' => $excelFixedA,
            'current_heads' => $excelRows($currentCodes),
            // True 455 head balance + Excel's displayed Inventory-row value (B25) + doubled total (B).
            'inventory_head_455' => ['code' => '455', 'head_id' => $excelHeadId['455'] ?? $chartHeadsBs['455']?->id, 'name' => $excelHeadName['455'] ?? ($chartHeadsBs['455'] ? ('455 - ' . $chartHeadsBs['455']->name) : '455 - Inventory'), 'balance' => $x('455')],
            'inventory_row_display' => $excelInventoryRow,
            'total_current_B_excel' => $excelCurrentB,
            'current_liab_heads' => $excelRows($currLiabCodes),
            'total_current_C' => $excelCurrentC,
            'net_current_BC' => $excelNetCurrent,
            'total_assets_less_current_excel' => $excelAssetsLessCurrent,
            'total_assets_less_current_correct' => $excelAssetsLessCurrentCorrect,
            'noncurrent_heads' => $excelRows($nonCurrCodes),
            'total_noncurrent_D' => $excelNonCurrentD,
            'net_assets_excel' => $excelNetAssets,
            'capital_heads' => $excelRows($capitalCodes),
            'total_capital_excel' => $excelCapital,
            'total_capital_all' => $excelCapitalAll,
            'notes' => [
                'Inventory row (B25) = SUM(B21:B24) per Excel; Total Current (B) = SUM(B21:B25) double-counts 451-454.',
                'Total Assets less Current Liabilities = B17(empty 0)+B45 per Excel R47; fixed assets excluded. Correct value provided separately.',
                'Total Capital and Reserves = SUM(B61:B62) per Excel R65; 603/604 excluded. Full sum provided separately.',
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

        return [
            'business_name'     => $businessName,
            'assets'            => array_values($assets),
            'liabilities'       => array_values($liabilities),
            'equity'            => array_values($equity),
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
        $rows = [];
        $rows[] = ['HD Accountancy — Profit & Loss (' . $report['business_name'] . ' | ' . $from . ' to ' . $to . ')', '', '', '', '', 'title'];
        $rows[] = ['Account', 'Cash', 'Bank', 'Card', 'Total', 'header'];
        $rows[] = ['TURNOVER', '', '', '', '', 'section'];
        foreach ($e['turnover_heads'] as $h) $rows[] = [$h['code'] . ' - ' . $h['head_name'], $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = ['OTHER INCOME (Investment + Non-Trading)', '', '', '', '', 'section'];
        foreach ($e['other_income_heads'] as $h) $rows[] = [$h['code'] . ' - ' . $h['head_name'], $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = ['Total Turnover (A)', $m($e['total_turnover_A'], 'cash'), $m($e['total_turnover_A'], 'bank'), $m($e['total_turnover_A'], 'card'), $m($e['total_turnover_A'], 'total'), 'total'];
        $rows[] = ['COST OF SALES / DIRECT EXPENSES', '', '', '', '', 'section'];
        foreach ($e['direct_heads'] as $h) $rows[] = [$h['code'] . ' - ' . $h['head_name'], $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = ['Total Cost of Sales (B = 201 only per Excel R25)', $m($e['total_cost_B_excel'], 'cash'), $m($e['total_cost_B_excel'], 'bank'), $m($e['total_cost_B_excel'], 'card'), $m($e['total_cost_B_excel'], 'total'), 'total'];
        $rows[] = ['GROSS PROFIT (C = A-B)', $m($e['gross_profit_C'], 'cash'), $m($e['gross_profit_C'], 'bank'), $m($e['gross_profit_C'], 'card'), $m($e['gross_profit_C'], 'total'), 'grand'];
        $rows[] = ['ADMINISTRATIVE COSTS (301-329)', '', '', '', '', 'section'];
        foreach ($e['admin_heads'] as $h) $rows[] = [$h['code'] . ' - ' . $h['head_name'], $m($h, 'cash'), $m($h, 'bank'), $m($h, 'card'), $m($h, 'total'), 'row'];
        $rows[] = ['Total Administrative Costs (D)', $m($e['total_admin_D'], 'cash'), $m($e['total_admin_D'], 'bank'), $m($e['total_admin_D'], 'card'), $m($e['total_admin_D'], 'total'), 'total'];
        $rows[] = ['OPERATING PROFIT (E = C-D)', $m($e['operating_profit_E'], 'cash'), $m($e['operating_profit_E'], 'bank'), $m($e['operating_profit_E'], 'card'), $m($e['operating_profit_E'], 'total'), 'grand'];
        $rows[] = ['Note: B sums 201 only (202-204 shown but excluded); A includes 107 Sales Refund as + — replicated exactly from the Excel sheet.', '', '', '', '', 'note'];

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
        $rows = [];
        $rows[] = ['HD Accountancy — Balance Sheet (' . $report['business_name'] . ' | As at ' . $asOf . ')', '', 'title'];
        $rows[] = ['Account', 'Amount', 'header'];
        $rows[] = ['FIXED ASSETS (401-407)', '', 'section'];
        foreach ($e['fixed_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Fixed Assets (A)', $v($e['total_fixed_A']), 'total'];
        $rows[] = ['CURRENT ASSETS', '', 'section'];
        foreach ($e['current_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Inventory row display (B25 = 451+452+453+454 per Excel)', $v($e['inventory_row_display']), 'row'];
        $rows[] = ['Inventory head 455 true balance (reference)', $v($e['inventory_head_455']['balance']), 'row'];
        $rows[] = ['Total Current Asset (B, double-counts per Excel)', $v($e['total_current_B_excel']), 'total'];
        $rows[] = ['CURRENT LIABILITIES (501-513)', '', 'section'];
        foreach ($e['current_liab_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Current Liabilities (C)', $v($e['total_current_C']), 'total'];
        $rows[] = ['Net Current Assets (B-C)', $v($e['net_current_BC']), 'total'];
        $rows[] = ['Total Assets less Current Liabilities (A+B-C = 0+B45 per Excel R47)', $v($e['total_assets_less_current_excel']), 'grand'];
        $rows[] = ['Correct A+Net incl. fixed assets (reference)', $v($e['total_assets_less_current_correct']), 'row'];
        $rows[] = ['NON-CURRENT LIABILITIES (551-554)', '', 'section'];
        foreach ($e['noncurrent_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Non-Current Liabilities (D)', $v($e['total_noncurrent_D']), 'total'];
        $rows[] = ['Net Assets (A+B-C-D)', $v($e['net_assets_excel']), 'grand'];
        $rows[] = ['CAPITAL AND RESERVES (601-604)', '', 'section'];
        foreach ($e['capital_heads'] as $r) $rows[] = [$r['name'], $v($r['balance']), 'row'];
        $rows[] = ['Total Capital and Reserves (601+602 only per Excel R65)', $v($e['total_capital_excel']), 'total'];
        $rows[] = ['Full capital incl. 603 + 604 (reference)', $v($e['total_capital_all']), 'row'];

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
        } elseif ($code) {
            $heads = $headQuery->where('code', $code)->get();
        } else {
            return response()->json(['message' => 'head_id or code required'], 422);
        }
        if ($heads->isEmpty()) {
            return response()->json(['message' => 'Account head not found'], 404);
        }
        $headIds = $heads->pluck('id')->all();

        $query = Transaction::with(['accountHead.accountType', 'receipt.client', 'receipt.detail'])
            ->whereIn('account_head_id', $headIds)
            ->whereHas('receipt', function ($q) use ($credentialId, $businessId, $from, $to, $asOf, $mode) {
                $q->whereNotIn('status', ['cancelled']);
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
            ->whereIn('type', ['payable', 'receivable']);
        if ($mode === 'bs') {
            $query->whereNull('parent_id');
        }
        if ($paymentMethod) {
            $query->whereHas('receipt.detail', fn($q) => $q->where('payment_method', $paymentMethod));
        }

        $txns = $query->orderBy('id', 'desc')->limit(200)->get();
        $rows = $txns->map(fn($t) => [
            'id' => $t->id,
            'date' => $t->receipt?->detail?->invoice_date,
            'receipt_id' => $t->receipt_id,
            'receipt_number' => $t->receipt?->receipt_number,
            'business' => trim(($t->receipt?->client?->name ?? '') . ' ' . ($t->receipt?->client?->last_name ?? '')),
            'head' => $t->accountHead ? ($t->accountHead->code . ' - ' . $t->accountHead->name) : '',
            'type' => $t->type,
            'payment_method' => $t->receipt?->detail?->payment_method,
            'amount' => (float) $t->total_amount,
        ]);

        return response()->json([
            'heads' => $heads->map(fn($h) => ['id' => $h->id, 'code' => $h->code, 'name' => $h->name, 'type' => $h->accountType?->name])->values(),
            'count' => $rows->count(),
            'total' => $rows->sum('amount'),
            'rows' => $rows,
        ]);
    }
}