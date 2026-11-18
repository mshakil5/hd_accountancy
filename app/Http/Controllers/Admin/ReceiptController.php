<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Models\ReceiptFile;
use Auth;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; 

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
            ->where('from_reconciliation', false)
            ->when(Auth::user() && Auth::user()->email === 'jane@gmail.com', function ($q) {
                $q->whereHas('client', function ($sub) {
                    $sub->whereHas('credential', function ($c) {
                        $c->where('email', 'david@gmail.com');
                    });
                });
            })
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
            ->withCount('transactions');

        // Link counts decide whether a receipt is still safe to delete. A
        // receipt used by reconciliation must be unlinked there first.
        foreach ([
            'bank_entries' => 'bank_entry_links',
            'bank_allocation_lines' => 'allocation_links',
            'bank_settlements' => 'settlement_links',
        ] as $table => $alias) {
            if (Schema::hasTable($table)) {
                $query->addSelect([$alias => DB::table($table)
                    ->selectRaw('count(*)')
                    ->whereColumn($table . '.receipt_id', 'receipts.id')]);
            }
        }

        return DataTables::of($query->latest())
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
                return $date ? Carbon::parse($date)->format('d/m/Y') : '-';
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
            ->addColumn('action', function ($r) {
                $html = '<div style="display: flex; gap: 5px; white-space: nowrap;">'
                    . '<a href="' . route('admin.receipt.show', $r->id) . '" class="btn btn-sm btn-primary">'
                    . '<i class="fa fa-eye"></i> View'
                    . '</a>';

                // Only receipts that are not ready/archived and have no
                // reconciliation or ledger links may be deleted.
                if ($this->receiptIsDeletable($r)) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger js-delete-receipt"'
                        . ' data-url="' . route('admin.receipt.destroy', $r->id) . '"'
                        . ' data-number="' . e($r->receipt_number ?: ('#' . $r->id)) . '">'
                        . '<i class="fa fa-trash"></i> Delete'
                        . '</button>';
                }

                return $html . '</div>';
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function searchClients(Request $request)
    {
        $search = $request->get('q');
        $selectedId = (int) $request->get('selected_id', 0);

        $query = ClientCredential::select('id', 'first_name', 'last_name', 'email', 'phone', 'status');

        if (auth()->user() && auth()->user()->email === 'jane@gmail.com') {
            $query->where('email', 'david@gmail.com');
        }

        // The dropdown is capped to the newest 20, so an older preset credential
        // would never be found and could not be shown as selected. Include it
        // explicitly (while still respecting any user scoping above).
        $selected = $selectedId ? (clone $query)->whereKey($selectedId)->first() : null;

        $clients = $query
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->latest()
            ->limit(20)
            ->get();

        if ($selected && !$clients->contains('id', $selected->id)) {
            $clients->push($selected);
        }

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
        $credentialId = $receipt->client?->client_credential_id;
        $heads = AccountHead::with('accountType:id,name')
            ->where('is_active', true)
            ->whereNotNull('account_type_id') // head-first flow: untyped heads are not usable yet
            ->where(function ($q) use ($credentialId) {
                $q->whereNull('client_credential_id');
                if ($credentialId) {
                    $q->orWhere('client_credential_id', $credentialId);
                }
            })
            ->orderBy('code')->get();

        $baseQuery = Receipt::whereHas('client', fn($q) => $q->where('client_credential_id', $credentialId))
            ->where('from_reconciliation', false);

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

        return view('admin.receipt.show', compact('receipt', 'heads', 'prev', 'next', 'credentialId', 'businesses', 'pendingPosition', 'pendingTotal', 'totalCount'));
    }

    public function getAccountHeads(Request $request)
    {
        $heads = AccountHead::with('accountType:id,name')
            ->when($request->filled('account_type_id'), function ($q) use ($request) {
                $q->where('account_type_id', $request->account_type_id);
            })
            ->where('is_active', true)
            ->whereNotNull('account_type_id') // head-first flow: untyped heads are not usable yet
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

        // Once a bank reconciliation has posted this receipt's journal, the
        // ledger must be reopened there first or the posting would be orphaned.
        if (DB::table('transactions')->where('receipt_id', $receipt->id)->whereNotNull('bank_entry_id')->exists()) {
            return response()->json([
                'status'  => 303,
                'message' => "<div class='alert alert-danger'>This receipt is already posted from an approved bank reconciliation. Reopen that reconciliation before editing it.</div>"
            ]);
        }

        $newStatus = $request->status;

        if (in_array($newStatus, ['ready', 'archived'])) {
            $errors = [];
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
        $paymentMethod = in_array($request->payment_method, ['cash', 'bank'], true) ? $request->payment_method : null;

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
                    'paid'            => $paymentMethod ? 1 : 0,
                    'payment_method'  => $paymentMethod,
                    'description'     => $request->description,
                ]
            );
        } elseif ($receipt->detail) {
            $receipt->detail->update([
                'invoice_date'   => $request->invoice_date ?: null,
                'due_date'       => $request->due_date ?: null,
                'invoice_number' => $request->invoice_number,
                'description'    => $request->description,
                'paid'           => $paymentMethod ? 1 : 0,
                'payment_method' => $paymentMethod,
            ]);
        }

        // Receipts are recognised in the ledger only after the bank statement
        // confirms them, so saving the receipt writes no journal rows here.

        return response()->json(['status' => 200, 'message' => 'Receipt saved successfully.']);
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

        // Upload file to DigitalOcean Spaces (S3)
        $path = Storage::disk('s3')->putFileAs($receiptDir, $file, $filename, 'public');

        $receipt->files()->create([
            'file_path'  => $path, // Store the S3 path
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
        // Delete from S3 (DigitalOcean Spaces)
        if (Storage::disk('s3')->exists($file->file_path)) {
            Storage::disk('s3')->delete($file->file_path);
        }
        $file->delete();

        return response()->json(['success' => true, 'message' => 'File deleted successfully.']);
    }

    public function downloadFile($id, $fileId)
    {
        $file = ReceiptFile::where('id', $fileId)->where('receipt_id', $id)->firstOrFail();

        // Check if file exists in DigitalOcean Spaces
        if (!Storage::disk('s3')->exists($file->file_path)) {
            abort(404, 'File not found in cloud storage.');
        }

        $downloadName = $file->file_name ?: basename($file->file_path);

        // Download directly from DigitalOcean Spaces
        return Storage::disk('s3')->download($file->file_path, $downloadName);
    }

    public function downloadAll($id)
    {
        $receipt = Receipt::with('files')->findOrFail($id);

        if ($receipt->files->isEmpty()) {
            abort(404, 'No files attached to this receipt.');
        }

        // If only 1 file, download it directly
        if ($receipt->files->count() === 1) {
            $file = $receipt->files->first();
            if (!Storage::disk('s3')->exists($file->file_path)) {
                abort(404, 'File not found in cloud storage.');
            }
            $downloadName = $file->file_name ?: basename($file->file_path);
            return Storage::disk('s3')->download($file->file_path, $downloadName);
        }

        // Create a temporary ZIP file
        $zipName = ($receipt->receipt_number ?: 'receipt-' . $receipt->id) . '.zip';
        $zipName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $zipName);
        $tmpPath = tempnam(sys_get_temp_dir(), 'receipt_') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create zip archive.');
        }

        $usedNames = [];
        foreach ($receipt->files as $index => $file) {
            // Check existence in S3
            if (!Storage::disk('s3')->exists($file->file_path)) {
                continue;
            }

            // Get file content from S3
            $fileContent = Storage::disk('s3')->get($file->file_path);

            $name = $file->file_name ?: basename($file->file_path);
            $name = preg_replace('/[\\\\\\/\\:*?"<>|]/', '_', $name);
            if (isset($usedNames[$name])) {
                $info = pathinfo($name);
                $name = ($info['filename'] ?? 'file') . '_' . ($index + 1) . '.' . ($info['extension'] ?? 'pdf');
            }
            $usedNames[$name] = true;
            
            // Add file content to ZIP directly from string
            $zip->addFromString($name, $fileContent);
        }
        $zip->close();

        if (!file_exists($tmpPath) || filesize($tmpPath) === 0) {
            @unlink($tmpPath);
            abort(404, 'No files available for download.');
        }

        // Send ZIP to browser and delete from temp directory after sending
        return response()->download($tmpPath, $zipName)->deleteFileAfterSend(true);
    }

    public function cancel($id)
    {
        $receipt = Receipt::findOrFail($id);
        if (in_array($receipt->status, ['archived', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'This receipt is already locked.']);
        }

        if (DB::table('transactions')->where('receipt_id', $receipt->id)->whereNotNull('bank_entry_id')->exists()) {
            return response()->json(['success' => false, 'message' => 'This receipt is posted from an approved bank reconciliation. Reopen that reconciliation before cancelling.']);
        }

        $receipt->update(['status' => 'cancelled', 'updated_by' => Auth::id()]);

        return response()->json(['success' => true, 'message' => 'Receipt cancelled successfully.']);
    }

    public function counts(Request $request)
    {
        $query = Receipt::query()->where('from_reconciliation', false);
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
        return view('admin.receipt.create');
    }

    public function store(Request $request)
    {
        $errors = [];
        if (!$request->client_credential_id) $errors[] = 'Client (Credential) is required.';
        if (!$request->client_id)            $errors[] = 'Business/Client is required.';

        if (in_array($request->status, ['ready', 'archived'])) {
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

            // Upload to S3 (same driver used by the mobile app API)
            $path = Storage::disk('s3')->putFileAs($receiptDir, $file, $filename, 'public');

            ReceiptFile::create([
                'receipt_id' => $receipt->id,
                'file_path'  => $path,
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
            $paymentMethod = in_array($request->payment_method, ['cash', 'bank'], true) ? $request->payment_method : null;

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
                'paid'            => $paymentMethod ? 1 : 0,
                'payment_method'  => $paymentMethod,
                'description'     => $request->description,
            ]);

            // No journal here: a receipt reaches the ledger only once a bank
            // statement line is matched to it and the reconciliation is approved.
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

    public function getBanksByClient(Request $request)
    {
        $clientId = (int) $request->input('client_id');
        if ($clientId > 0) {
            $banks = DB::table('reconciliation_bank_accounts')
                ->where('client_id', $clientId)
                ->where('active', 1)
                ->orderBy('bank_name')
                ->get(['id', 'bank_name', 'account_last_four']);
        } else {
            $credentialId = (int) $request->input('client_credential_id');
            $clientIds = \App\Models\Client::where('client_credential_id', $credentialId)->where('status', 1)->pluck('id')->all();
            $banks = DB::table('reconciliation_bank_accounts')
                ->whereIn('client_id', $clientIds)
                ->where('active', 1)
                ->orderBy('bank_name')
                ->get(['id', 'bank_name', 'account_last_four']);
        }
        return response()->json($banks);
    }

    /**
     * A receipt can be deleted only while it is still work in progress
     * (pending / to review / cancelled) and nothing in the books points at it.
     */
    private function receiptIsDeletable($receipt): bool
    {
        if (!empty($receipt->from_reconciliation)) {
            return false;
        }

        if (in_array((string) ($receipt->status ?? 'pending'), ['ready', 'archived'], true)) {
            return false;
        }

        foreach (['transactions_count', 'bank_entry_links', 'allocation_links', 'settlement_links'] as $key) {
            if ((int) ($receipt->{$key} ?? 0) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Permanently delete an unused receipt: its rows, its files on S3/local
     * storage and the folder that holds them. Ready/archived receipts and
     * receipts tied to a reconciliation or ledger posting are refused.
     */
    public function destroy($id)
    {
        $receipt = Receipt::with('files')->findOrFail($id);

        if (!empty($receipt->from_reconciliation)) {
            return back()->with('error', 'This receipt was created by bank reconciliation and cannot be deleted here.');
        }

        if (in_array((string) $receipt->status, ['ready', 'archived'], true)) {
            return back()->with('error', 'Ready or archived receipts cannot be deleted.');
        }

        if ($receipt->transactions()->exists()) {
            return back()->with('error', 'This receipt already has ledger postings. Remove those first, then delete the receipt.');
        }

        $linkLabels = [
            'bank_entries' => 'bank statement row',
            'bank_allocation_lines' => 'bank split allocation',
            'bank_settlements' => 'bank settlement',
        ];
        foreach ($linkLabels as $table => $label) {
            if (Schema::hasTable($table) && DB::table($table)->where('receipt_id', $receipt->id)->exists()) {
                return back()->with('error', 'This receipt is linked to a ' . $label . '. Remove that link in Bank Reconciliation before deleting it.');
            }
        }

        $receiptId = $receipt->id;
        $paths = $receipt->files->pluck('file_path')->filter()->all();

        DB::transaction(function () use ($receiptId) {
            DB::table('receipt_files')->where('receipt_id', $receiptId)->delete();
            DB::table('receipt_details')->where('receipt_id', $receiptId)->delete();
            DB::table('receipts')->where('id', $receiptId)->delete();
        });

        // Remove the stored PDFs (and the receipt's own folder) so the space is
        // actually reclaimed. Failures are logged, never rolled back.
        $targets = [];
        foreach ($paths as $path) {
            $targets[] = $path;
            $dir = trim(dirname($path), '/\\');
            if ($dir !== '' && $dir !== '.' && basename($dir) === (string) $receiptId) {
                foreach (['s3', 'local'] as $disk) {
                    try {
                        foreach (Storage::disk($disk)->allFiles($dir) as $file) {
                            $targets[] = $file;
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        foreach (array_unique($targets) as $path) {
            foreach (['s3', 'local'] as $disk) {
                try {
                    if (!Storage::disk($disk)->exists($path)) {
                        continue;
                    }
                    Storage::disk($disk)->delete($path);
                    if (Storage::disk($disk)->exists($path)) {
                        logger()->warning('Receipt file could not be deleted from storage.', [
                            'disk' => $disk,
                            'path' => $path,
                            'receipt_id' => $receiptId,
                        ]);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        // Legacy app uploads wrote the PDFs into public/images/receipts with a
        // relative path, so remove those copies (and their receipt folder) too.
        foreach (array_unique($paths) as $path) {
            try {
                $absolute = public_path($path);
                if (is_file($absolute)) {
                    @unlink($absolute);
                    if (is_file($absolute)) {
                        logger()->warning('Receipt file could not be deleted from public storage.', [
                            'path' => $absolute,
                            'receipt_id' => $receiptId,
                        ]);
                    }
                }

                $dir = dirname($absolute);
                if (is_dir($dir) && basename($dir) === (string) $receiptId) {
                    foreach (glob($dir . '/*') ?: [] as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                        }
                    }
                    @rmdir($dir);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', 'Receipt deleted and its stored files removed.');
    }

    private function getReceiptDirectory($client, $receiptId)
    {
        $clientName = $this->sanitizeDirName($client->name ?? 'unknown');
        $businessName = $this->sanitizeDirName($client->business_name ?? $client->name ?? 'unknown');
        $year = now()->format('Y');

        return "images/receipts/{$clientName}/{$businessName}/{$year}/{$receiptId}";
    }

    private function sanitizeDirName($name)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name ?? 'unknown');
    }
}