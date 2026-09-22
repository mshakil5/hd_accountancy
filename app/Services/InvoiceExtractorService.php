<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceExtractorService
{
    private $config;

    public function __construct()
    {
        // Auto-detect OS
        if (PHP_OS_FAMILY === 'Windows') {
            $this->config = [
                'ghostscript' => '"C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe"',
                'tesseract'   => '"C:\\Program Files\\Tesseract-OCR\\tesseract.exe"',
            ];
        } else {
            $this->config = [
                'ghostscript' => '/usr/bin/gs', 
                'tesseract'   => '/usr/bin/tesseract',
            ];
        }
    }

    /**
     * Extract specific fields from Image-based Invoice PDF using CLI OCR
     */
    /**
     * Extract specific fields from Image-based Invoice PDF using CLI OCR
     */
    public function extractInvoiceData(string $pdfPath): array
    {
        try {
            $tempDir = sys_get_temp_dir();
            $baseName = uniqid('invoice_ocr_');
            $imagePath = $tempDir . '/' . $baseName . '.png';
            $textPath = $tempDir . '/' . $baseName;
 
            $gsCmd = "{$this->config['ghostscript']} -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"{$imagePath}\" \"{$pdfPath}\" 2>&1";
            $gsOutput = shell_exec($gsCmd);

            if (!file_exists($imagePath)) { 
                Log::error('Ghostscript Failed', ['command' => $gsCmd, 'output' => $gsOutput]);
                return ['error' => 'Ghostscript failed to convert PDF: ' . $gsOutput];
            }
 
            $tessCmd = "{$this->config['tesseract']} \"{$imagePath}\" \"{$textPath}\" -l eng 2>&1";
            $tessOutput = shell_exec($tessCmd);

            $textFilePath = $textPath . '.txt';
            if (!file_exists($textFilePath)) {
                return ['error' => 'Tesseract failed: ' . $tessOutput];
            }

            $text = file_get_contents($textFilePath);

            @unlink($imagePath);
            @unlink($textFilePath);

            Log::info('Raw OCR Text Extracted (CLI)', ['text' => $text]);

            return $this->parseExtractedText($text);

        } catch (Exception $e) {
            return ['error' => 'OCR Failed: ' . $e->getMessage()];
        }
    }

    /**
     * Parse the raw text to find specific invoice fields
     */
    private function parseExtractedText(string $text): array
    {
        // 1. Clean up the text
        $text = str_replace(['£', '€', '$', 'GBP', 'EUR'], ' ', $text);
        $text = str_replace(['V.A.T.', 'V A T'], 'VAT', $text);
        // Replace multiple spaces and newlines with a single space
        $text = preg_replace('/\s+/', ' ', $text);

        $data = [
            'net_amount'    => $this->extractValue($text, ['amount excl', 'goods value', 'goods total', 'sub total', 'subtotal', 'order value', 'net amount', '\bnet\b']),
            'vat_amount'    => $this->extractValue($text, ['vat value', 'total vat', 'vat total', 'vat amount', 'value added tax', '\bvat\b']),
            'total_amount'  => $this->extractValue($text, ['invoice total', 'total due', 'grand total', 'goods supplied', 'outstanding', '\btotal\b']),
            'vat_percent'   => $this->extractPercent($text),
            'tax_percent'   => $this->extractPercent($text),
        ];

        return $data;
    }

        /**
     * Helper function to find numeric value based on keywords
     */
    private function extractValue(string $text, array $keywords): ?string
    {
        foreach ($keywords as $keyword) {
            $pattern = '/' . $keyword . '[\s\:\$£€]*?(\d+(?:,\d{3})*(?:\.\d{2})?)/i';
            if (preg_match($pattern, $text, $matches)) {
                return str_replace(',', '', $matches[1]);
            }
            
            $pattern2 = '/' . $keyword . '[\s\:\$£€]*?(\d+)/i';
            if (preg_match($pattern2, $text, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Helper function to extract Tax/VAT percentage
     */
    private function extractPercent(string $text): ?string
    {
        if (preg_match('/(?:@|vat\s*%|tax\s*%|vat\s*rate|tax\s*rate)\s*[:]*\s*(\d+\.?\d*)\s*%/i', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }
}