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
        $clients = ClientCredential::select('id', 'first_name', 'last_name')->get();
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

        $businessName = 'All Businesses';
        if ($businessId) {
            $client = Client::find($businessId);
            $businessName = $client ? trim(($client->name ?? '') . ' ' . ($client->last_name ?? '')) : 'Unknown';
        } elseif ($credentialId) {
            $cred = ClientCredential::find($credentialId);
            $businessName = $cred ? trim(($cred->first_name ?? '') . ' ' . ($cred->last_name ?? '')) : 'Unknown';
        }

        \Log::info('PL Result', ['income' => $totalIncome, 'expense' => $totalExpense, 'net' => $netProfit]);

        return response()->json([
            'from'          => Carbon::parse($from)->format('d M Y'),
            'to'            => Carbon::parse($to)->format('d M Y'),
            'business_name' => $businessName,
            'income'        => $incomeData,
            'expenses'      => $expenseData,
            'total_income'  => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit'    => $netProfit,
        ]);
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
        $clients = ClientCredential::select('id', 'first_name', 'last_name')->get();
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
        $clients = ClientCredential::select('id', 'first_name', 'last_name')->get();
        return view('admin.accounting.balance_sheet', compact('clients'));
    }

    public function balanceSheetData(Request $request)
    {
        $asOf         = $request->as_of ?? Carbon::now()->toDateString();
        $credentialId = $request->client_credential_id;
        $businessId   = $request->client_id;

        \Log::info('BS Request', ['as_of' => $asOf, 'cred' => $credentialId, 'biz' => $businessId]);

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
                if (!isset($assets[$headKey])) $assets[$headKey] = ['name' => $headLabel, 'balance' => 0];
                $assets[$headKey]['balance'] += $balance;
            } elseif ($category === 'liability') {
                if (!isset($liabilities[$headKey])) $liabilities[$headKey] = ['name' => $headLabel, 'balance' => 0];
                $liabilities[$headKey]['balance'] += $balance;
            } elseif ($category === 'equity') {
                if (!isset($equity[$headKey])) $equity[$headKey] = ['name' => $headLabel, 'balance' => 0];
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

        return response()->json([
            'as_of'             => Carbon::parse($asOf)->format('d M Y'),
            'business_name'     => $businessName,
            'assets'            => array_values($assets),
            'liabilities'       => array_values($liabilities),
            'equity'            => array_values($equity),
            'net_profit'        => $netProfit,
            'total_assets'      => $totalAssets,
            'total_liabilities' => $totalLiab,
            'total_equity'      => $totalEquity,
            'total_liab_equity' => $totalLiabEquity,
        ]);
    }
}