<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\AccountType;
use App\Models\ClientCredential;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\ReceiptFile;
use App\Models\Transaction;
use Auth;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function index()
    {
        return view('admin.receipt.index');
    }

    public function datatable()
    {
        $query = Receipt::with(['client.credential', 'detail.accountHead.accountType'])
            ->when(request('client_credential_id'), function ($q) {
                $q->whereHas('client', function ($subQ) {
                    $subQ->where('client_credential_id', request('client_credential_id'));
                });
            })
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('payment_method'), fn($q) => $q->whereHas('detail', fn($q) => $q->where('payment_method', request('payment_method'))))
            ->when(request('paid'), fn($q) => $q->whereHas('detail', fn($q) => $q->where('paid', request('paid') == 'yes' ? 1 : 0)))
            ->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('client_name', function ($r) {
                $credential = $r->client?->credential;
                return $credential ? trim(($credential->first_name ?? '') . ' ' . ($credential->last_name ?? '')) : '-';
            })
            ->addColumn('business_name', function ($r) {
                return $r->client ? trim(($r->client->name ?? '') . ' ' . ($r->client->last_name ?? '')) : '-';
            })
            ->addColumn('account_type', fn($r) => $r->detail?->accountHead?->accountType?->name ?? '-')
            ->addColumn('account_head', function ($r) {
                $head = $r->detail?->accountHead;
                return $head ? $head->code . ' - ' . $head->name : '-';
            })
            ->addColumn('invoice_date', function ($r) {
                $date = $r->detail?->invoice_date ?? $r->receipt_date;
                return $date ? Carbon::parse($date)->format('d M y') : '-';
            })
            ->addColumn('invoice_number', fn($r) => $r->detail?->invoice_number ?? '-')
            ->addColumn('net_amount', fn($r) => $r->detail?->net_amount ? '£' . $r->detail->net_amount : '-')
            ->addColumn('vat_amount', fn($r) => $r->detail?->vat_amount ? '£' . $r->detail->vat_amount : '-')
            ->addColumn('tax_amount', fn($r) => $r->detail?->tax_amount ? '£' . $r->detail->tax_amount : '-')
            ->addColumn('total_amount', fn($r) => $r->detail?->total_amount ? '£' . $r->detail->total_amount : '-')
            ->addColumn('payment_method', fn($r) => $r->detail?->payment_method ?? '-')
            ->addColumn('status_badge', function ($r) {
                $colors = [
                    'pending'   => 'warning',
                    'to_review' => 'info',
                    'ready'     => 'success',
                    'cancelled' => 'danger',
                    'archived'  => 'secondary',
                ];
                $colorClass = $colors[$r->status] ?? 'warning';
                return '<button class="btn btn-sm btn-' . $colorClass . '" style="pointer-events:none; border-radius: 20px; font-size: 11px; color: #fff;">'
                    . ucfirst(str_replace('_', ' ', $r->status)) .
                    '</button>';
            })
            ->addColumn('action', fn($r) => '
                <div style="display: flex; gap: 5px; white-space: nowrap;">
                    <a href="' . route('admin.receipt.show', $r->id) . '" class="btn btn-sm btn-primary">
                        <i class="fa fa-eye"></i> View
                    </a>
                </div>')
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function searchClients(Request $request)
    {
        $search = $request->get('q');
        $clients = ClientCredential::select('id', 'first_name', 'last_name')
            ->when($search, function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            })
            ->limit(20)->get();

        $formattedClients = [];
        foreach ($clients as $client) {
            $formattedClients[] = [
                'id' => $client->id,
                'text' => trim($client->first_name . ' ' . $client->last_name)
            ];
        }
        return response()->json($formattedClients);
    }

    public function show($id)
    {
        $receipt = Receipt::with(['files', 'detail.accountHead.accountType', 'client'])->findOrFail($id);
        $accountTypes = AccountType::where('is_active', true)->get();

        $currentAccountTypeId = $receipt->detail?->accountHead?->account_type_id;
        $heads = $currentAccountTypeId
            ? AccountHead::with('taxRate')->where('account_type_id', $currentAccountTypeId)->where('is_active', true)->get()
            : collect();

        $prev = Receipt::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;
        $next = Receipt::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;

        return view('admin.receipt.show', compact('receipt', 'accountTypes', 'heads', 'prev', 'next'));
    }

    public function getAccountHeads(Request $request)
    {
        $heads = AccountHead::with('taxRate')
            ->where('account_type_id', $request->account_type_id)
            ->where('is_active', true)
            ->get();

        return response()->json($heads);
    }

    public function update(Request $request, $id)
    {
        $receipt = Receipt::findOrFail($id);

        if (in_array($receipt->status, ['archived', 'cancelled'])) {
            return response()->json([
                'status'  => 303,
                'message' => "<div class='alert alert-danger'>This receipt is locked ({$receipt->status}) and cannot be modified.</div>"
            ]);
        }

        $newStatus = $request->status;

        if (in_array($newStatus, ['ready', 'archived'])) {
            $errors = [];
            if (!$request->account_type_id) $errors[] = 'Account Type is required.';
            if (!$request->account_head_id) $errors[] = 'Account Head is required.';
            if (!$request->invoice_date)    $errors[] = 'Invoice Date is required.';
            if (!$request->net_amount)      $errors[] = 'Net Amount is required.';

            if (count($errors)) {
                return response()->json([
                    'status'  => 303,
                    'message' => "<div class='alert alert-danger'><ul class='mb-0'><li>" . implode('</li><li>', $errors) . "</li></ul></div>"
                ]);
            }
        }

        $receipt->update([
            'status'     => $newStatus,
            'updated_by' => Auth::id()
        ]);

        $netAmount   = (float)$request->net_amount;
        $taxAmount   = (float)($request->tax_amount ?? 0);
        $vatAmount   = (float)($request->vat_amount ?? 0);
        $totalAmount = $netAmount + $taxAmount + $vatAmount;

        if ($request->account_head_id) {
            ReceiptDetail::updateOrCreate(
                ['receipt_id' => $receipt->id],
                [
                    'account_head_id' => $request->account_head_id,
                    'invoice_date'    => $request->invoice_date ?: null,
                    'due_date'        => $request->due_date ?: null,
                    'invoice_number'  => $request->invoice_number,
                    'net_amount'      => $netAmount ?: null,
                    'tax_amount'      => $taxAmount,
                    'vat_amount'      => $vatAmount,
                    'total_amount'    => $totalAmount ?: null,
                    'paid'            => $request->paid == 'yes' ? 1 : 0,
                    'payment_method'  => $request->paid == 'yes' ? $request->payment_method : null,
                    'description'     => $request->description,
                ]
            );
        } elseif ($receipt->detail) {
            $receipt->detail->update([
                'invoice_date'   => $request->invoice_date ?: null,
                'due_date'       => $request->due_date ?: null,
                'invoice_number' => $request->invoice_number,
                'description'    => $request->description,
                'paid'           => $request->paid == 'yes' ? 1 : 0,
                'payment_method' => $request->paid == 'yes' ? $request->payment_method : null,
            ]);
        }

        $receipt->transactions()->delete();

        if (in_array($newStatus, ['ready', 'archived'])) {
            $head = AccountHead::with('accountType')->find($request->account_head_id);
            if ($head && $head->accountType) {
                $normalBalance = $head->accountType->normal_balance;
                $type          = $normalBalance === 'debit' ? 'payable' : 'receivable';

                $firstTransaction = Transaction::create([
                    'transaction_uid' => 'TXN-' . strtoupper(Str::random(10)),
                    'receipt_id'      => $receipt->id,
                    'account_head_id' => $head->id,
                    'type'            => $type,
                    'amount'          => $netAmount,
                    'tax_percent'     => (float)($request->tax_percent ?? 0),
                    'tax_amount'      => $taxAmount,
                    'total_amount'    => $totalAmount,
                    'created_by'      => Auth::id(),
                ]);

                if ($request->paid == 'yes') {
                    $secondType = ($type === 'payable') ? 'paid' : 'received';
                    Transaction::create([
                        'transaction_uid' => 'TXN-' . strtoupper(Str::random(10)),
                        'receipt_id'      => $receipt->id,
                        'account_head_id' => $head->id,
                        'type'            => $secondType,
                        'amount'          => $totalAmount,
                        'tax_percent'     => 0,
                        'tax_amount'      => 0,
                        'total_amount'    => $totalAmount,
                        'payment_method'  => $request->payment_method,
                        'parent_id'       => $firstTransaction->id,
                        'created_by'      => Auth::id(),
                    ]);
                }
            }
        }

        return response()->json(['status' => 200, 'message' => 'Receipt and accounting transactions processed successfully.']);
    }

    public function uploadFile(Request $request, $id)
    {
        $receipt = Receipt::findOrFail($id);
        if (in_array($receipt->status, ['archived', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Cannot upload files to a locked receipt.']);
        }

        $request->validate(['file' => 'required|file|max:10240']);
        $file = $request->file('file');

        $filename = time() . '_' . rand(100, 999) . '.' . $file->getClientOriginalExtension();
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $fileType = Str::startsWith($mime, 'image/') ? 'image' : ($mime === 'application/pdf' ? 'pdf' : 'unknown');
        $file->move(public_path('images/receipts'), $filename);

        $receipt->files()->create([
            'file_path' => 'images/receipts/' . $filename,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $fileType,
            'mime_type' => $mime,
            'file_size' => $size,
        ]);

        return response()->json(['success' => true, 'message' => 'File uploaded successfully.']);
    }

    public function deleteFile($id, $fileId)
    {
        $receipt = Receipt::findOrFail($id);
        if (in_array($receipt->status, ['archived', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Cannot delete files from a locked receipt.']);
        }

        $file = ReceiptFile::where('id', $fileId)->where('receipt_id', $id)->firstOrFail();
        if (file_exists(public_path($file->file_path))) unlink(public_path($file->file_path));
        $file->delete();

        return response()->json(['success' => true, 'message' => 'File deleted successfully.']);
    }

    public function cancel($id)
    {
        $receipt = Receipt::findOrFail($id);
        if (in_array($receipt->status, ['archived', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'This receipt is already locked.']);
        }

        $receipt->transactions()->delete();
        $receipt->update(['status' => 'cancelled', 'updated_by' => Auth::id()]);

        return response()->json(['success' => true, 'message' => 'Receipt cancelled successfully.']);
    }

    public function bill($id)
    {
        $receipt = Receipt::with([
            'files',
            'detail.accountHead.accountType',
            'detail.accountHead.taxRate',
            'client',
            'transactions'
        ])->findOrFail($id);

        return view('admin.receipt.bill', compact('receipt'));
    }

    public function counts()
    {
        return response()->json([
            'pending'   => Receipt::where('status', 'pending')->count(),
            'to_review' => Receipt::where('status', 'to_review')->count(),
            'ready'     => Receipt::where('status', 'ready')->count(),
            'cancelled' => Receipt::where('status', 'cancelled')->count(),
            'archived'  => Receipt::where('status', 'archived')->count(),
            'total'     => Receipt::count(),
        ]);
    }
}