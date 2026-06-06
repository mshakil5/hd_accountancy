<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\AccountType;
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
        $query = Receipt::with(['client', 'detail.accountHead.accountType'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('payment_method'), fn($q) => $q->whereHas('detail', fn($q) => $q->where('payment_method', request('payment_method'))))
            ->when(request('paid'), fn($q) => $q->whereHas('detail', fn($q) => $q->where('paid', request('paid') == 'yes' ? 1 : 0)))
            ->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('client_name', fn($r) => trim($r->client->name . ' ' . $r->client->last_name))
            ->addColumn('business_name', fn($r) => $r->client->business_name)
            ->addColumn('account_type', fn($r) => $r->detail?->accountHead?->accountType?->name ?? '-')
            ->addColumn('account_head', fn($r) => $r->detail?->accountHead?->name ?? '-')
            ->addColumn('invoice_date', function($r) {
                $date = $r->detail?->invoice_date ?? $r->receipt_date;

                if (!$date) return '-';

                return Carbon::parse($date)->format('d M y');
            })
            ->addColumn('invoice_number', fn($r) => $r->detail?->invoice_number ?? '-')
            ->addColumn('net_amount', fn($r) => $r->detail?->net_amount ? 'GBP ' . $r->detail->net_amount : '-')
            ->addColumn('vat_amount', fn($r) => $r->detail?->vat_amount ? 'GBP ' . $r->detail->vat_amount : '-')
            ->addColumn('total_amount', fn($r) => $r->detail?->total_amount ? 'GBP ' . $r->detail->total_amount : '-')
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

    public function show($id)
    {
        $receipt = Receipt::with([
            'client',
            'files',
            'detail.accountHead.accountType',
            'detail.accountHead.taxRate',
            'transactions',
        ])->findOrFail($id);

        $accountHeads = AccountHead::with('accountType', 'taxRate')
            ->where('is_active', 1)
            ->get();

        $accountTypes = AccountType::where('is_active', 1)->select('id', 'name')->get();

        return view('admin.receipt.show', compact('receipt', 'accountHeads', 'accountTypes'));
    }

    public function update(Request $request, $id)
    {
        $receipt = Receipt::findOrFail($id);

        if ($receipt->status === 'archived') {
            return response()->json(['status' => 303, 'message' => "<div class='alert alert-danger'>Archived receipts cannot be edited.</div>"]);
        }

        $newStatus = $request->status;

        if (!in_array($newStatus, ['cancelled', 'archived'])) {
            $missingFields = empty($request->account_head_id) || empty($request->invoice_date) || empty($request->net_amount);

            if ($newStatus === 'ready' && $missingFields) {
                return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Please fill Account Head, Invoice Date, and Net Amount to mark as Ready.</div>"]);
            }

            if ($newStatus === 'ready' && $request->paid == 'yes' && empty($request->payment_method)) {
                return response()->json(['status' => 303, 'message' => "<div class='alert alert-warning'>Please select Payment Method.</div>"]);
            }

            if ($missingFields) {
                $newStatus = 'to_review';
            }
        }

        if (in_array($newStatus, ['cancelled', 'archived'])) {
            $receipt->update(['status' => $newStatus]);
            return response()->json(['status' => 300, 'message' => 'Receipt updated successfully.']);
        }

        $accountHead = AccountHead::with('taxRate')->find($request->account_head_id);
        $taxPercent  = $accountHead?->taxRate?->rate ?? 0;
        $netAmount   = $request->net_amount ?? 0;
        $vatAmount   = round($netAmount * ($taxPercent / 100), 2);
        $totalAmount = round($netAmount + $vatAmount, 2);

        ReceiptDetail::updateOrCreate(
            ['receipt_id' => $receipt->id],
            [
                'account_head_id' => $request->account_head_id,
                'invoice_date'    => $request->invoice_date,
                'due_date'        => $request->due_date,
                'invoice_number'  => $request->invoice_number,
                'net_amount'      => $netAmount,
                'vat_amount'      => $vatAmount,
                'total_amount'    => $totalAmount,
                'paid'            => $request->paid == 'yes' ? 1 : 0,
                'payment_method'  => $request->paid == 'yes' ? $request->payment_method : null,
                'description'     => $request->description,
            ]
        );

        $receipt->update(['status' => $newStatus]);

        if ($newStatus === 'ready') {
            $accountType = $accountHead->accountType;
            $firstType   = in_array($accountType->category, ['asset', 'expense']) ? 'payable' : 'receivable';
            $paidType    = $firstType === 'payable' ? 'paid' : 'received';

            $firstTransaction = $receipt->transactions()->whereNull('parent_id')->first();
            if (!$firstTransaction) {
                $firstTransaction = Transaction::create([
                    'transaction_uid' => 'TXN-' . strtoupper(Str::random(10)),
                    'receipt_id'      => $receipt->id,
                    'account_head_id' => $request->account_head_id,
                    'type'            => $firstType,
                    'amount'          => $netAmount,
                    'tax_percent'     => $taxPercent,
                    'tax_amount'      => $vatAmount,
                    'total_amount'    => $totalAmount,
                    'payment_method'  => null,
                    'parent_id'       => null,
                    'created_by'      => Auth::id(),
                ]);
            }

            $existingPaidTxn = $receipt->transactions()->whereNotNull('parent_id')->first();

            if ($request->paid == 'yes') {
                if (!$existingPaidTxn) {
                    Transaction::create([
                        'transaction_uid' => 'TXN-' . strtoupper(Str::random(10)),
                        'receipt_id'      => $receipt->id,
                        'account_head_id' => $request->account_head_id,
                        'type'            => $paidType,
                        'amount'          => $netAmount,
                        'tax_percent'     => $taxPercent,
                        'tax_amount'      => $vatAmount,
                        'total_amount'    => $totalAmount,
                        'payment_method'  => $request->payment_method,
                        'parent_id'       => $firstTransaction->id,
                        'created_by'      => Auth::id(),
                    ]);
                }
            } else {
                if ($existingPaidTxn) {
                    $existingPaidTxn->delete();
                }
            }
        }

        return response()->json(['status' => 300, 'message' => 'Receipt updated successfully.']);
    }

    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        $receipt  = Receipt::findOrFail($id);
        $file     = $request->file('file');
        $mime     = $file->getClientMimeType();
        $size     = $file->getSize();
        $fileType = $file->extension() === 'pdf' ? 'pdf' : 'image';
        $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
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
        $file = ReceiptFile::where('id', $fileId)
            ->where('receipt_id', $id)
            ->firstOrFail();

        if (file_exists(public_path($file->file_path))) {
            unlink(public_path($file->file_path));
        }
        $file->delete();

        return response()->json(['success' => true, 'message' => 'File deleted successfully.']);
    }

    public function archive($id)
    {
        Receipt::findOrFail($id)->update(['status' => 'archived']);
        return response()->json(['success' => true, 'message' => 'Receipt archived.']);
    }

    public function cancel($id)
    {
        Receipt::findOrFail($id)->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Receipt cancelled.']);
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
