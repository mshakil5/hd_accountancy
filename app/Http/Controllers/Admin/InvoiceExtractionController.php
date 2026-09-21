<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InvoiceExtractorService;
use App\Models\ReceiptFile; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // এই লাইনটি অবশ্যই যুক্ত করতে হবে

class InvoiceExtractionController extends Controller
{
    protected $extractorService;

    public function __construct(InvoiceExtractorService $extractorService)
    {
        $this->extractorService = $extractorService;
    }

    /**
     * Extract data from PDF and return as JSON
     */
    public function extractData(Request $request, $fileId)
    {
        $file = ReceiptFile::findOrFail($fileId);
        
        // Ensure file is a PDF
        if ($file->file_type !== 'pdf') {
            return response()->json(['success' => false, 'message' => 'File is not a PDF.']);
        }

        $filePath = public_path($file->file_path); // storage path হলে storage_path() ব্যবহার করবেন

        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'File not found on server.']);
        }

        // সার্ভিস থেকে ডাটা এক্সট্র্যাক্ট করে আনা হচ্ছে
        $extractedData = $this->extractorService->extractInvoiceData($filePath);

        // ── LOG RECORD করার কোড ──
        if (isset($extractedData['error'])) {
            // যদি কোনো এরর হয়, তবে এরর লগ করবে
            Log::error('PDF Extraction Failed', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'error_message' => $extractedData['error']
            ]);
            return response()->json(['success' => false, 'message' => $extractedData['error']]);
        }

        // সফল হলে কি কি ডাটা পাওয়া গেছে তা laravel.log ফাইলে রেকর্ড করবে
        Log::info('PDF Data Extracted Successfully', [
            'file_id' => $fileId,
            'file_path' => $file->file_path,
            'extracted_data' => $extractedData
        ]);

        return response()->json([
            'success' => true,
            'data' => $extractedData
        ]);
    }
}