<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceExtractorService
{
    private $config;

    /**
     * Process-local memo of OCR text, keyed by file identity.
     *
     * One upload OCRs the same PDF twice (the uploader reads the text and the
     * verification engine reads it again), and OCR is by far the slowest step,
     * so the result is kept for the rest of the request/process.
     *
     * @var array<string,string>
     */
    private static array $ocrMemo = [];

    /** Keep the memo small so a long-running worker cannot grow it without bound. */
    private const OCR_MEMO_LIMIT = 8;

    public function __construct()
    {
        // Resolve the binaries instead of trusting one hard-coded version: Ghostscript
        // installs under gs<version>, and PATH differs between a PC and the VPS.
        $this->config = [
            'ghostscript' => $this->findBinary(
                [(string) getenv('GHOSTSCRIPT_BIN')],
                PHP_OS_FAMILY === 'Windows' ? ['gswin64c.exe', 'gswin32c.exe'] : ['gs'],
                PHP_OS_FAMILY === 'Windows'
                    ? ['C:/Program Files/gs/gs*/bin/gswin64c.exe', 'C:/Program Files (x86)/gs/gs*/bin/gswin32c.exe']
                    : ['/usr/bin/gs', '/usr/local/bin/gs', '/opt/homebrew/bin/gs']
            ),
            'tesseract' => $this->findBinary(
                [(string) getenv('TESSERACT_BIN')],
                ['tesseract'],
                PHP_OS_FAMILY === 'Windows'
                    ? ['C:/Program Files/Tesseract-OCR/tesseract.exe', 'C:/Program Files (x86)/Tesseract-OCR/tesseract.exe']
                    : ['/usr/bin/tesseract', '/usr/local/bin/tesseract', '/opt/homebrew/bin/tesseract']
            ),
        ];
    }

    /**
     * First usable binary from an explicit setting, PATH, then known install locations.
     * The returned value is already shell-quoted so existing command templates stay valid.
     */
    private function findBinary(array $explicit, array $names, array $globs): string
    {
        foreach ($explicit as $candidate) {
            if ($candidate !== '' && $this->isExecutable($candidate)) {
                return escapeshellarg($candidate);
            }
        }
        foreach ($names as $name) {
            $found = $this->onPath($name);
            if ($found) {
                return escapeshellarg($found);
            }
        }
        foreach ($globs as $glob) {
            $matches = glob($glob) ?: [];
            // Newest Ghostscript wins when several versions are installed.
            usort($matches, fn ($a, $b) => strnatcasecmp($b, $a));
            foreach ($matches as $match) {
                if (is_file($match)) {
                    return escapeshellarg($match);
                }
            }
        }
        // Fall back to the bare name so a missing tool surfaces a clear "not found" error.
        return $names[0];
    }

    private function isExecutable(string $path): bool
    {
        if (is_file($path)) {
            return true;
        }
        return PHP_OS_FAMILY === 'Windows' ? $this->onPath($path) !== null : false;
    }

    private function onPath(string $name): ?string
    {
        $null = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';
        $command = PHP_OS_FAMILY === 'Windows'
            ? 'where ' . escapeshellarg($name) . ' ' . $null
            : 'command -v ' . escapeshellarg($name) . ' ' . $null;
        $output = (string) @shell_exec($command);
        $first = trim((string) strtok($output, "\r\n"));
        if ($first === '') {
            return null;
        }
        if (PHP_OS_FAMILY === 'Windows' && !is_file($first)) {
            return null;
        }
        return $first;
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

            // Log::info('Raw OCR Text Extracted (CLI)', ['text' => $text]);

            return $this->parseExtractedText($text);

        } catch (Exception $e) {
            return ['error' => 'OCR Failed: ' . $e->getMessage()];
        }
    }

    /**
     * OCR every page of a PDF and return the raw text.
     *
     * Shares the same Ghostscript + Tesseract toolchain as extractInvoiceData(),
     * so it works anywhere invoice OCR works (e.g. the production VPS).
     */
    public function extractRawText(string $pdfPath): string
    {
        $key = $this->ocrMemoKey($pdfPath);
        if ($key !== null && array_key_exists($key, self::$ocrMemo)) {
            return self::$ocrMemo[$key];
        }

        $text = '';
        try {
            $tempDir = sys_get_temp_dir();
            $base = uniqid('pdf_ocr_');
            $imagePattern = $tempDir . DIRECTORY_SEPARATOR . $base . '-%d.png';

            $gsCmd = "{$this->config['ghostscript']} -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r300 -sOutputFile=\"{$imagePattern}\" \"{$pdfPath}\" 2>&1";
            $gsOutput = (string) @shell_exec($gsCmd);
            $images = glob($tempDir . DIRECTORY_SEPARATOR . $base . '-*.png') ?: [];
            if (!$images) {
                Log::warning('Ghostscript produced no OCR images', ['path' => $pdfPath, 'output' => mb_substr($gsOutput, 0, 1000)]);
            }

            foreach ($images as $image) {
                $outBase = $tempDir . DIRECTORY_SEPARATOR . pathinfo($image, PATHINFO_FILENAME);
                @shell_exec("{$this->config['tesseract']} \"{$image}\" \"{$outBase}\" -l eng 2>&1");
                $textFile = $outBase . '.txt';
                if (file_exists($textFile)) {
                    $text .= file_get_contents($textFile) . "\n";
                    @unlink($textFile);
                }
                @unlink($image);
            }
        } catch (\Throwable $e) {
            Log::error('Bank/invoice PDF OCR failed', ['error' => $e->getMessage()]);
            $text = '';
        }

        if ($key !== null) {
            if (count(self::$ocrMemo) >= self::OCR_MEMO_LIMIT) {
                array_shift(self::$ocrMemo);
            }
            self::$ocrMemo[$key] = $text;
        }
        Log::info('Raw PDF OCR completed', ['path' => $pdfPath, 'length' => mb_strlen(trim($text))]);
        return $text;
    }

    /**
     * Identity of a file for memoisation: real path plus size and mtime, so a
     * changed file is never served from a stale entry.
     */
    private function ocrMemoKey(string $pdfPath): ?string
    {
        $real = realpath($pdfPath);
        if ($real === false || !is_file($real)) {
            return null;
        }
        $stat = @stat($real);
        return $real . '|' . ($stat['size'] ?? 0) . '|' . ($stat['mtime'] ?? 0);
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
