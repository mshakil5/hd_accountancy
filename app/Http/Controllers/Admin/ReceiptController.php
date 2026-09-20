<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\AccountType;
use App\Models\Client;
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
        $users = \App\Models\User::select('id', 'first_name', 'last_name')->orderBy('first_name')->get();
        return view('admin.receipt.index', compact('users'));
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
            ->when(request('business'), fn($q) => $q->where('client_id', request('business')))
            ->when(request('supplier'), fn($q) => $q->where('supplier', 'LIKE', '%' . request('supplier') . '%'))
            ->when(request('invoice_number'), fn($q) => $q->whereHas('detail', fn($q) => $q->where('invoice_number', 'LIKE', '%' . request('invoice_number') . '%')))
            ->when(request('notes'), fn($q) => $q->where('notes', 'LIKE', '%' . request('notes') . '%'))
            ->when(request('created_by'), fn($q) => $q->where('created_by', request('created_by')))
            ->when(request('date_from'), function ($q) {
                $dateFrom = request('date_from');
                $dateType = request('date_type', 'invoice');
                if ($dateType === 'created') {
                    $q->where('created_at', '>=', $dateFrom);
                } elseif ($dateType === 'receipt') {
                    $q->where('receipt_date', '>=', $dateFrom);
                } else {
                    $q->where(function ($sub) use ($dateFrom) {
                        $sub->whereHas('detail', fn($d) => $d->where('invoice_date', '>=', $dateFrom))
                            ->orWhere(function ($sub2) use ($dateFrom) {
                                $sub2->whereDoesntHave('detail')->where('receipt_date', '>=', $dateFrom);
                            });
                    });
                }
            })
            ->when(request('date_to'), function ($q) {
                $dateTo = request('date_to');
                $dateType = request('date_type', 'invoice');
                if ($dateType === 'created') {
                    $q->where('created_at', '<=', $dateTo);
                } elseif ($dateType === 'receipt') {
                    $q->where('receipt_date', '<=', $dateTo);
                } else {
                    $q->where(function ($sub) use ($dateTo) {
                        $sub->whereHas('detail', fn($d) => $d->where('invoice_date', '<=', $dateTo))
                            ->orWhere(function ($sub2) use ($dateTo) {
                                $sub2->whereDoesntHave('detail')->where('receipt_date', '<=', $dateTo);
                            });
                    });
                }
            })
            ->when(request('amount_min'), fn($q) => $q->whereHas('detail', fn($d) => $d->where('total_amount', '>=', request('amount_min'))))
            ->when(request('amount_max'), fn($q) => $q->whereHas('detail', fn($d) => $d->where('total_amount', '<=', request('amount_max'))))
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
            ->addColumn('supplier', fn($r) => $r->supplier ?? '-')
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
        $clients = ClientCredential::select('id', 'first_name', 'last_name', 'email', 'phone', 'status')
            ->when($search, function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            })
            ->latest()->limit(20)->get();

        $formattedClients = [];
        foreach ($clients as $client) {
            $formattedClients[] = [
                'id' => $client->id,
                'text' => trim($client->first_name . ' ' . $client->last_name),
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status
            ];
        }
        return response()->json($formattedClients);
    }

    public function show($id)
    {
        $receipt = Receipt::with(['files', 'detail.accountHead.accountType', 'client.credential'])->findOrFail($id);
        $accountTypes = AccountType::where('is_active', true)->get();

        $currentAccountTypeId = $receipt->detail?->accountHead?->account_type_id;
        $heads = $currentAccountTypeId
            ? AccountHead::with('taxRate')
                ->where('account_type_id', $currentAccountTypeId)
                ->where('is_active', true)
                ->where(function ($q) use ($credentialId) {
                    $q->whereNull('client_credential_id');
                    if ($credentialId) {
                        $q->orWhere('client_credential_id', $credentialId);
                    }
                })
                ->orderBy('code')->get()
            : collect();

        $credentialId = $receipt->client?->client_credential_id;

        $baseQuery = Receipt::whereHas('client', fn($q) => $q->where('client_credential_id', $credentialId));

        $pendingQuery = (clone $baseQuery)->whereIn('status', ['pending', 'to_review'])->orderBy('id', 'asc');
        $pendingTotal = (clone $pendingQuery)->count();
        $pendingBefore = (clone $pendingQuery)->where('id', '<', $id)->count();
        $pendingPosition = in_array($receipt->status, ['pending', 'to_review']) ? ($pendingBefore + 1) : 0;

        $completedCount = (clone $baseQuery)->whereNotIn('status', ['pending', 'to_review'])->count();
        $totalCount = (clone $baseQuery)->count();

        $prev = (clone $baseQuery)->where('id', '<', $id)
            ->whereIn('status', ['pending', 'to_review'])
            ->orderBy('id', 'desc')->first()?->id;
        $next = (clone $baseQuery)->where('id', '>', $id)
            ->whereIn('status', ['pending', 'to_review'])
            ->orderBy('id', 'asc')->first()?->id;

        $businesses = \App\Models\Client::where('client_credential_id', $credentialId)
            ->where('status', 1)->get();

        return view('admin.receipt.show', compact('receipt', 'accountTypes', 'heads', 'prev', 'next', 'credentialId', 'businesses', 'pendingPosition', 'pendingTotal', 'totalCount'));
    }

    public function getAccountHeads(Request $request)
    {
        $heads = AccountHead::with('taxRate')
            ->where('account_type_id', $request->account_type_id)
            ->where('is_active', true)
            ->where(function ($q) use ($request) {
                $q->whereNull('client_credential_id');
                if ($request->client_credential_id) {
                    $q->orWhere('client_credential_id', $request->client_credential_id);
                }
            })
            ->orderBy('code')
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
            'client_id'  => $request->client_id ?: $receipt->client_id,
            'supplier'   => $request->supplier,
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
        $receipt = Receipt::with('client')->findOrFail($id);
        if (in_array($receipt->status, ['archived', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Cannot upload files to a locked receipt.']);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:51200',
        ]);

        $existingCount = $receipt->files()->count();
        if ($existingCount >= 10) {
            return response()->json(['success' => false, 'message' => 'Cannot exceed 10 PDF files per receipt.']);
        }

        $file = $request->file('file');
        $mime = $file->getClientMimeType();
        $size = $file->getSize();

        $receiptDir = $this->getReceiptDirectory($receipt->client, $receipt->id);
        $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path($receiptDir), $filename);

        $receipt->files()->create([
            'file_path'  => $receiptDir . '/' . $filename,
            'file_name'  => $file->getClientOriginalName(),
            'file_type'  => 'pdf',
            'mime_type'  => $mime,
            'file_size'  => $size,
        ]);

        return response()->json(['success' => true, 'message' => 'File uploaded successfully.']);
    }

    public function deleteFile($id, $fileId)
    {
        $receipt = Receipt::findOrFail($id);
        if (in_array($receipt->status, ['archived', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Cannot delete files from a locked receipt.']);
        }

        if ($receipt->files()->count() <= 1) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the last file. Receipt must have at least one file.']);
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

    public function counts(Request $request)
    {
        $query = Receipt::query();
        if ($request->client_credential_id) {
            $query->whereHas('client', function ($q) {
                $q->where('client_credential_id', request('client_credential_id'));
            });
        }
        return response()->json([
            'pending'   => (clone $query)->where('status', 'pending')->count(),
            'to_review' => (clone $query)->where('status', 'to_review')->count(),
            'ready'     => (clone $query)->where('status', 'ready')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'archived'  => (clone $query)->where('status', 'archived')->count(),
            'total'     => (clone $query)->count(),
        ]);
    }

    public function create()
    {
        $accountTypes = AccountType::where('is_active', true)->get();
        return view('admin.receipt.create', compact('accountTypes'));
    }

    public function store(Request $request)
    {
        $errors = [];
        if (!$request->client_credential_id) $errors[] = 'Client (Credential) is required.';
        if (!$request->client_id)            $errors[] = 'Business/Client is required.';

        if (in_array($request->status, ['ready', 'archived'])) {
            if (!$request->account_type_id) $errors[] = 'Account Type is required.';
            if (!$request->account_head_id) $errors[] = 'Account Head is required.';
            if (!$request->invoice_date)    $errors[] = 'Invoice Date is required.';
            if (!$request->net_amount)      $errors[] = 'Net Amount is required.';
        }

        $request->validate([
            'files'   => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:pdf|max:51200',
        ]);

        if (count($errors)) {
            return response()->json([
                'status'  => 303,
                'message' => "<div class='alert alert-danger'><ul class='mb-0'><li>" . implode('</li><li>', $errors) . "</li></ul></div>"
            ]);
        }

        $client = \App\Models\Client::findOrFail($request->client_id);

        $receipt = Receipt::create([
            'client_id'      => $client->id,
            'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'receipt_date'   => $request->receipt_date ?? now()->toDateString(),
            'notes'          => $request->notes,
            'supplier'       => $request->supplier,
            'status'         => $request->status ?? 'pending',
            'created_by'     => Auth::id(),
        ]);

        $receiptDir = $this->getReceiptDirectory($client, $receipt->id);

        foreach ($request->file('files') as $file) {
            $mime     = $file->getClientMimeType();
            $size     = $file->getSize();
            $fileType = $file->extension() === 'pdf' ? 'pdf' : 'image';
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path($receiptDir), $filename);

            ReceiptFile::create([
                'receipt_id' => $receipt->id,
                'file_path'  => $receiptDir . '/' . $filename,
                'file_name'  => $file->getClientOriginalName(),
                'file_type'  => $fileType,
                'mime_type'  => $mime,
                'file_size'  => $size,
            ]);
        }

        if ($request->account_head_id) {
            $netAmount   = (float)$request->net_amount;
            $taxAmount   = (float)($request->tax_amount ?? 0);
            $vatAmount   = (float)($request->vat_amount ?? 0);
            $totalAmount = $netAmount + $taxAmount + $vatAmount;

            ReceiptDetail::create([
                'receipt_id'      => $receipt->id,
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
            ]);

            if (in_array($request->status, ['ready', 'archived'])) {
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
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Receipt created successfully.',
            'id'      => $receipt->id,
        ]);
    }

    public function getClientsByCredential(Request $request)
    {
        $clients = \App\Models\Client::where('client_credential_id', $request->client_credential_id)
            ->where('status', 1)
            ->with(['clientType'])
            ->get()
            ->map(function ($c) {
                $name     = trim(($c->name ?? '') . ' ' . ($c->last_name ?? ''));
                $business = $c->business_name ?? $c->company_name ?? '';
                $type     = $c->clientType?->name ?? '';
                return [
                    'id'   => $c->id,
                    'name' => $name . ($business ? " ({$business})" : ''),
                    'info' => implode(' | ', array_filter([$name, $business, $type, $c->city])),
                ];
            });

        return response()->json($clients);
    }

    private function getReceiptDirectory($client, $receiptId)
    {
        $clientName = $this->sanitizeDirName($client->name ?? 'unknown');
        $businessName = $this->sanitizeDirName($client->business_name ?? $client->name ?? 'unknown');
        $year = now()->format('Y');

        $path = public_path("images/receipts/{$clientName}/{$businessName}/{$year}/{$receiptId}");

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return "images/receipts/{$clientName}/{$businessName}/{$year}/{$receiptId}";
    }

    private function sanitizeDirName($name)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name ?? 'unknown');
    }
}