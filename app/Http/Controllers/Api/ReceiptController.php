<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\ReceiptFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function all(Request $request)
    {
        $user = $request->user();

        if ($user instanceof User) {
            $clientIds = Client::pluck('id');
        } else {
            $clientIds = Client::where('client_credential_id', $user->id)
                ->where('status', true)
                ->pluck('id');
        }

        $receipts = Receipt::whereIn('client_id', $clientIds)
            ->withCount('files')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'receipt_number' => $r->receipt_number,
                'receipt_date'   => $r->receipt_date?->format('d M Y'),
                'notes'          => $r->notes,
                'status'         => $r->status,
                'file_count'     => $r->files_count,
                'created_at'     => $r->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json(['data' => $receipts], 200);
    }

    public function index(Request $request, $businessId)
    {
        $client = Client::findOrFail($businessId);

        $receipts = Receipt::where('client_id', $client->id)
            ->withCount('files')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'receipt_number' => $r->receipt_number,
                'receipt_date'   => $r->receipt_date?->format('d M Y'),
                'notes'          => $r->notes,
                'status'         => $r->status,
                'file_count'     => $r->files_count,
                'created_at'     => $r->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json(['data' => $receipts], 200);
    }

    public function show(Request $request, $id)
    {
        $receipt = Receipt::with(['files', 'client'])->findOrFail($id);

        $user = $request->user();
        if ($user instanceof User) {
            // Admin/manager/staff: all clients
        } else {
            if ($receipt->client->client_credential_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        return response()->json([
            'data' => [
                'id'             => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'receipt_date'   => $receipt->receipt_date?->format('d M Y'),
                'notes'          => $receipt->notes,
                'status'         => $receipt->status,
                'client'         => [
                    'id'            => $receipt->client->id,
                    'name'          => trim($receipt->client->name . ' ' . $receipt->client->last_name),
                    'business_name' => $receipt->client->business_name,
                ],
                'files' => $receipt->files->map(fn($f) => [
                    'id'        => $f->id,
                    'file_name' => $f->file_name,
                    'file_type' => $f->file_type,
                    'mime_type' => $f->mime_type,
                    'file_size' => $f->file_size,
                    'url'       => $f->url,
                ]),
                'created_at' => $receipt->created_at->format('Y-m-d H:i'),
            ],
        ], 200);
    }

    public function store(Request $request, $businessId)
    {
        try {
            \Log::info('Receipt store called', [
                'businessId' => $businessId,
                'user_id' => $request->user()?->id,
                'user_type' => get_class($request->user()),
                'has_files' => $request->hasFile('files'),
                'file_count' => $request->file('files') ? count($request->file('files')) : 0,
                'all_input' => array_keys($request->all()),
                'content_type' => $request->header('Content-Type'),
            ]);

            $validator = \Validator::make($request->all(), [
                'files'        => 'required|array|min:1',
                'files.*'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'receipt_date' => 'nullable|date',
                'notes'        => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                \Log::error('Receipt validation failed', $validator->errors()->toArray());
                return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $totalBytes = collect($request->file('files'))->sum(fn($f) => $f->getSize());
            if ($totalBytes > 5 * 1024 * 1024) {
                return response()->json(['message' => 'Total file size must not exceed 5MB.'], 422);
            }

            $user = $request->user();

            if ($user instanceof User) {
                $client = Client::findOrFail($businessId);
            } else {
                $client = Client::where('id', $businessId)
                    ->where('client_credential_id', $user->id)
                    ->where('status', true)
                    ->first();
            }

            if (!$client) {
                \Log::error('Client not found for businessId', ['businessId' => $businessId, 'user_id' => $user->id]);
                return response()->json(['message' => 'Business not found.'], 404);
            }

            $receipt = Receipt::create([
                'client_id'      => $client->id,
                'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'receipt_date'   => $request->receipt_date ?? now()->toDateString(),
                'notes'          => $request->notes,
                'status'         => 'pending',
                'created_by'     => $request->user()->id,
            ]);

            $receiptDir = $this->getReceiptDirectory($client, $receipt->id);

            foreach ($request->file('files') as $file) {
                $mime = $file->getClientMimeType();
                $size = $file->getSize();

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

            return response()->json([
                'message' => 'Receipt uploaded successfully.',
                'data'    => [
                    'id'             => $receipt->id,
                    'receipt_number' => $receipt->receipt_number,
                    'status'         => $receipt->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Receipt store exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    private function getReceiptDirectory($client, $receiptId)
    {
        $clientName = $this->sanitizeDirName($client->name);
        $businessName = $this->sanitizeDirName($client->business_name ?? $client->name);
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

    public function update(Request $request, $id)
    {
        $receipt = Receipt::with('client')->findOrFail($id);

        if ($receipt->status !== 'pending') {
            return response()->json(['message' => 'Only pending receipts can be edited.'], 422);
        }

        $user = $request->user();
        if (!$user instanceof User) {
            if ($receipt->client->client_credential_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        $request->validate([
            'receipt_date' => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
            'supplier'     => 'nullable|string',
        ]);

        $receipt->update($request->only(['receipt_date', 'notes', 'supplier']));

        return response()->json([
            'message' => 'Receipt updated successfully.',
            'data'    => [
                'id'             => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'receipt_date'   => $receipt->receipt_date?->format('d M Y'),
                'notes'          => $receipt->notes,
                'status'         => $receipt->status,
            ],
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $receipt = Receipt::with(['files', 'client'])->findOrFail($id);

        if ($receipt->status !== 'pending') {
            return response()->json(['message' => 'Only pending receipts can be deleted.'], 422);
        }

        $user = $request->user();
        if (!$user instanceof User) {
            if ($receipt->client->client_credential_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        foreach ($receipt->files as $file) {
            $fullPath = public_path($file->file_path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $file->delete();
        }

        $receipt->delete();

        return response()->json(['message' => 'Receipt deleted successfully.'], 200);
    }

    public function deleteFile(Request $request, $receiptId, $fileId)
    {
        $receipt = Receipt::with(['files', 'client'])->findOrFail($receiptId);

        if ($receipt->status !== 'pending') {
            return response()->json(['message' => 'Only pending receipts can have files removed.'], 422);
        }

        $user = $request->user();
        if (!$user instanceof User) {
            if ($receipt->client->client_credential_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        $file = ReceiptFile::where('receipt_id', $receiptId)->where('id', $fileId)->firstOrFail();

        $fullPath = public_path($file->file_path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted successfully.'], 200);
    }

    public function addFile(Request $request, $receiptId)
    {
        $receipt = Receipt::with('client')->findOrFail($receiptId);

        if ($receipt->status !== 'pending') {
            return response()->json(['message' => 'Only pending receipts can accept new files.'], 422);
        }

        $user = $request->user();
        if (!$user instanceof User) {
            if ($receipt->client->client_credential_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|mimes:pdf|max:5120',
        ]);

        $totalBytes = collect($request->file('files'))->sum(fn($f) => $f->getSize());
        if ($totalBytes > 5 * 1024 * 1024) {
            return response()->json(['message' => 'Total file size must not exceed 5MB.'], 422);
        }

        $receiptDir = $this->getReceiptDirectory($receipt->client, $receipt->id);

        foreach ($request->file('files') as $file) {
            $mime = $file->getClientMimeType();
            $size = $file->getSize();
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

        return response()->json(['message' => 'Files added successfully.'], 201);
    }
}