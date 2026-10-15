<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InvoiceExtractorService;
use App\Models\ReceiptFile; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // Make sure to import Storage

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

        // Create a temporary file path in the system temp directory
        $tempDir = sys_get_temp_dir();
        $tempFilePath = $tempDir . '/' . uniqid('invoice_pdf_', true) . '.pdf';

        try {
            // Download the file from DigitalOcean Spaces (S3)
            $fileContent = Storage::disk('s3')->get($file->file_path);
            
            // Save the content to the temporary file
            file_put_contents($tempFilePath, $fileContent);

            if (!file_exists($tempFilePath)) {
                return response()->json(['success' => false, 'message' => 'Failed to download file from cloud storage.']);
            }

            // Pass the temporary local file path to the extractor service
            $extractedData = $this->extractorService->extractInvoiceData($tempFilePath);

        } catch (\Exception $e) {
            Log::error('File retrieval failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['success' => false, 'message' => 'Error fetching file from storage.']);
        } finally {
            // Always delete the temporary file after processing
            if (file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }

        if (isset($extractedData['error'])) {
            Log::error('PDF Extraction Failed', [
                'file_id' => $fileId,
                'file_path' => $file->file_path,
                'error_message' => $extractedData['error']
            ]);
            return response()->json(['success' => false, 'message' => $extractedData['error']]);
        }

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