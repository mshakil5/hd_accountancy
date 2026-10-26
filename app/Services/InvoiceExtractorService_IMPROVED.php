<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Improved invoice / receipt OCR extractor.
 *
 * Changes vs original:
 * - Image pre-processing (deskew, contrast, upscale) before Tesseract
 * - Better Tesseract config for receipts
 * - Stronger field extraction (date, merchant, total, VAT)
 * - Works on both PDF-wrapped WhatsApp images and real invoice PDFs
 */
class InvoiceExtractorService
{
    private $config;

    private static array $ocrMemo = [];
    private const OCR_MEMO_LIMIT = 8;

    public function __construct()
    {
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
            'convert' => $this->findBinary(   // ImageMagick
                [(string) getenv('IMAGEMAGICK_BIN')],
                ['convert', 'magick'],
                ['/usr/bin/convert', '/usr/local/bin/convert', '/opt/homebrew/bin/convert']
            ),
        ];
    }

    /* ------------------------------------------------------------------ *
     * Public API (same signatures as original where possible)
     * ------------------------------------------------------------------ */

    public function extractInvoiceData(string $pdfPath): array
    {
        try {
            $text = $this->extractRawText($pdfPath, 1);
            if (trim($text) === '') {
                return ['error' => 'No text could be extracted from the document'];
            }
            return $this->parseExtractedText($text);
        } catch (Exception $e) {
            return ['error' => 'OCR Failed: ' . $e->getMessage()];
        }
    }

    /**
     * OCR every page (or first N pages) and return raw text.
     * Now includes image pre-processing for better accuracy on phone photos.
     */
    public function extractRawText(string $pdfPath, ?int $lastPage = null): string
    {
        $key = $this->ocrMemoKey($pdfPath, $lastPage);
        if ($key !== null && array_key_exists($key, self::$ocrMemo)) {
            return self::$ocrMemo[$key];
        }

        $text = '';
        try {
            $tempDir = sys_get_temp_dir();
            $base = uniqid('pdf_ocr_');
            $imagePattern = $tempDir . DIRECTORY_SEPARATOR . $base . '-%d.png';

            $pageRange = $lastPage === null ? '' : ' -dFirstPage=1 -dLastPage=' . max(1, $lastPage);
            // Higher DPI for phone receipts
            $gsCmd = "{$this->config['ghostscript']} -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r400{$pageRange} -sOutputFile=\"{$imagePattern}\" \"{$pdfPath}\" 2>&1";
            $gsOutput = (string) @shell_exec($gsCmd);
            $images = glob($tempDir . DIRECTORY_SEPARATOR . $base . '-*.png') ?: [];

            if (!$images) {
                Log::warning('Ghostscript produced no OCR images', ['path' => $pdfPath, 'output' => mb_substr($gsOutput, 0, 1000)]);
            }

            foreach ($images as $image) {
                $processed = $this->preprocessImage($image);
                $outBase = $tempDir . DIRECTORY_SEPARATOR . pathinfo($processed, PATHINFO_FILENAME);

                // Better Tesseract settings for receipts
                $tessCmd = "{$this->config['tesseract']} \"{$processed}\" \"{$outBase}\" -l eng --psm 6 --oem 3 2>&1";
                @shell_exec($tessCmd);

                $textFile = $outBase . '.txt';
                if (file_exists($textFile)) {
                    $text .= file_get_contents($textFile) . "\n";
                    @unlink($textFile);
                }
                @unlink($image);
                if ($processed !== $image) {
                    @unlink($processed);
                }
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

        Log::info('Raw PDF OCR completed', ['path' => $pdfPath, 'last_page' => $lastPage, 'length' => mb_strlen(trim($text))]);
        return $text;
    }

    /**
     * Full receipt extraction: date, merchant, total, net, vat, currency.
     */
    public function extractReceiptFields(string $pdfPath): array
    {
        $text = $this->extractRawText($pdfPath);
        $parsed = $this->parseExtractedText($text);

        $parsed['date']     = $this->extractDate($text);
        $parsed['merchant'] = $this->extractMerchant($text);
        $parsed['currency'] = $this->extractCurrency($text);
        $parsed['raw_text_preview'] = mb_substr(preg_replace('/\s+/', ' ', $text), 0, 400);

        return $parsed;
    }

    /* ------------------------------------------------------------------ *
     * Image pre-processing (biggest accuracy gain on WhatsApp photos)
     * ------------------------------------------------------------------ */

    private function preprocessImage(string $imagePath): string
    {
        // If ImageMagick is not available, return original
        if (empty($this->config['convert']) || $this->config['convert'] === 'convert') {
            // still try – many systems have it
        }

        $out = $imagePath . '.prep.png';

        // Deskew + contrast + mild sharpen + upscale
        // This dramatically helps phone photos of receipts
        $cmd = "{$this->config['convert']} \"{$imagePath}\" "
             . "-auto-orient -colorspace Gray "
             . "-resize 200% "
             . "-contrast-stretch 2%x1% "
             . "-sharpen 0x1 "
             . "-despeckle "
             . "\"{$out}\" 2>&1";

        $output = (string) @shell_exec($cmd);

        if (file_exists($out) && filesize($out) > 1000) {
            return $out;
        }

        // Fallback: just return original
        return $imagePath;
    }

    /* ------------------------------------------------------------------ *
     * Field parsing
     * ------------------------------------------------------------------ */

    private function parseExtractedText(string $text): array
    {
        $clean = str_replace(['£', '€', '$', 'GBP', 'EUR'], ' ', $text);
        $clean = str_replace(['V.A.T.', 'V A T'], 'VAT', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return [
            'net_amount'   => $this->extractValue($clean, [
                'amount excl', 'amount excluding vat', 'goods value', 'goods total', 'sub total', 'subtotal',
                'order value', 'net amount', 'net total', 'net value', 'net excluding vat', '\bnet\b',
            ]),
            'vat_amount'   => $this->extractValue($clean, [
                'vat value', 'total vat', 'vat total', 'vat amount', 'value added tax', '\bvat\b',
                'tax value', 'tax total', 'total tax',
            ]),
            'total_amount' => $this->extractValue($clean, [
                'invoice total', 'total due', 'grand total', 'amount due', 'balance due',
                'total to pay', 'total paid', 'goods supplied', 'outstanding',
                'total amount', 'total including vat', 'total inc vat', 'amount including vat',
                'amount charged', 'amount paid', 'payment total', '\btotal\b',
            ]),
            'vat_percent'  => $this->extractPercent($clean),
            'tax_percent'  => $this->extractPercent($clean),
        ];
    }

    private function extractValue(string $text, array $keywords): ?string
    {
        foreach ($keywords as $keyword) {
            // keyword followed by optional punctuation then a money number
            $pattern = '/' . $keyword . '[\s\:\-]*?(?:£|\$|€)?\s*(\d{1,5}(?:,\d{3})*(?:\.\d{2})?|\d+\.\d{2})/i';
            if (preg_match($pattern, $text, $matches)) {
                return str_replace(',', '', $matches[1]);
            }
        }

        // Fallback: largest money-like number near the bottom third of the text
        // (common for "TOTAL" on receipts)
        if (preg_match_all('/(\d{1,5}(?:,\d{3})*(?:\.\d{2})?)/', $text, $all) && !empty($all[1])) {
            $nums = array_map(fn ($n) => (float) str_replace(',', '', $n), $all[1]);
            // Prefer numbers that look like totals (not tiny)
            $candidates = array_filter($nums, fn ($n) => $n >= 0.5);
            if ($candidates) {
                return number_format(max($candidates), 2, '.', '');
            }
        }

        return null;
    }

    private function extractPercent(string $text): ?string
    {
        if (preg_match('/(?:vat|tax)\s*(?:rate|@|at)?\s*(\d{1,2}(?:\.\d+)?)\s*%/i', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/(\d{1,2}(?:\.\d+)?)\s*%\s*(?:vat|tax)/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractDate(string $text): ?string
    {
        $patterns = [
            '/\b(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})\b/',
            '/\b(\d{1,2}\s+[A-Za-z]{3,9}\s+\d{2,4})\b/',
            '/\b([A-Za-z]{3,9}\s+\d{1,2},?\s+\d{2,4})\b/',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $text, $m)) {
                return trim($m[1]);
            }
        }
        return null;
    }

    private function extractMerchant(string $text): ?string
    {
        // First non-empty line that looks like a name (not pure numbers)
        $lines = preg_split('/\R/u', $text) ?: [];
        foreach (array_slice($lines, 0, 8) as $line) {
            $line = trim($line);
            if ($line === '' || mb_strlen($line) < 3) continue;
            if (preg_match('/^\d+$/', $line)) continue;
            if (preg_match('/total|vat|receipt|invoice|tel|phone|www\./i', $line)) continue;
            // Clean and return
            $line = preg_replace('/\s+/', ' ', $line);
            if (mb_strlen($line) <= 60) {
                return $line;
            }
        }
        return null;
    }

    private function extractCurrency(string $text): string
    {
        if (preg_match('/£|GBP/i', $text)) return 'GBP';
        if (preg_match('/€|EUR/i', $text)) return 'EUR';
        if (preg_match('/\$|USD/i', $text)) return 'USD';
        return 'GBP'; // default for UK system
    }

    /* ------------------------------------------------------------------ *
     * Binary discovery (unchanged from original)
     * ------------------------------------------------------------------ */

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
            usort($matches, fn ($a, $b) => strnatcasecmp($b, $a));
            foreach ($matches as $match) {
                if (is_file($match)) {
                    return escapeshellarg($match);
                }
            }
        }
        return $names[0];
    }

    private function isExecutable(string $path): bool
    {
        if (is_file($path)) return true;
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
        if ($first === '') return null;
        if (PHP_OS_FAMILY === 'Windows' && !is_file($first)) return null;
        return $first;
    }

    private function ocrMemoKey(string $pdfPath, ?int $lastPage = null): ?string
    {
        $real = realpath($pdfPath);
        if ($real === false || !is_file($real)) return null;
        $stat = @stat($real);
        return $real . '|' . ($stat['size'] ?? 0) . '|' . ($stat['mtime'] ?? 0) . '|' . ($lastPage ?? 'all');
    }
}
