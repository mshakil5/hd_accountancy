<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\ReceiptFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function all(Request $request)
    {
        $clientIds = Client::where('client_credential_id', $request->user()->id)
            ->pluck('id');

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
        $client = Client::where('id', $businessId)
            ->where('client_credential_id', $request->user()->id)
            ->firstOrFail();

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
        $receipt = Receipt::whereHas('client', function ($q) use ($request) {
            $q->where('client_credential_id', $request->user()->id);
        })
            ->with(['files', 'client'])
            ->findOrFail($id);

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
        $request->validate([
            'files'        => 'required|array|min:1',
            'files.*'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'receipt_date' => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $totalBytes = collect($request->file('files'))->sum(fn($f) => $f->getSize());
        if ($totalBytes > 5 * 1024 * 1024) {
            return response()->json(['message' => 'Total file size must not exceed 5MB.'], 422);
        }

        $client = Client::where('id', $businessId)
            ->where('client_credential_id', $request->user()->id)
            ->firstOrFail();

        $receipt = Receipt::create([
            'client_id'      => $client->id,
            'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'receipt_date'   => $request->receipt_date ?? now()->toDateString(),
            'notes'          => $request->notes,
            'status'         => 'pending',
            'created_by'     => $request->user()->id,
        ]);

        foreach ($request->file('files') as $file) {

            $mime = $file->getClientMimeType();
            $size = $file->getSize();

            $fileType = $file->extension() === 'pdf' ? 'pdf' : 'image';

            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

            $file->move(public_path('images/receipts'), $filename);

            ReceiptFile::create([
                'receipt_id' => $receipt->id,
                'file_path'  => 'images/receipts/' . $filename,
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
    }
}