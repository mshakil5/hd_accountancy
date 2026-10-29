<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use Smalot\PdfParser\Parser;

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
            $embedded = $this->extractEmbeddedPdfText($pdfPath);
            // Invoices normally place their totals on page one. Combining both
            // sources lets OCR recover image-only labels without throwing away
            // accurate selectable amounts from the PDF.
            $ocr = $this->extractRawText($pdfPath, 1);
            $text = trim($ocr . "\n" . $embedded);
            if ($text === '') {
                return ['error' => 'No text could be read from this PDF.'];
            }
            Log::info('Invoice text extracted', [
                'path' => $pdfPath,
                'embedded_length' => mb_strlen(trim($embedded)),
                'ocr_length' => mb_strlen(trim($ocr)),
            ]);
            $result = $this->reconcileAmounts($this->parseExtractedText($text));
            Log::info('Invoice OCR fields detected', [
                'path' => $pdfPath,
                'net_amount_found' => $result['net_amount'] !== null,
                'vat_amount_found' => $result['vat_amount'] !== null,
                'total_amount_found' => $result['total_amount'] !== null,
                'vat_percent_found' => $result['vat_percent'] !== null,
            ]);
            if (!array_filter($result)) {
                $this->logUndetectedInvoiceMoneyLines($pdfPath, $text);
            }
            return $result;

        } catch (Exception $e) {
            return ['error' => 'OCR Failed: ' . $e->getMessage()];
        }
    }

    private function extractEmbeddedPdfText(string $pdfPath): string
    {
        try {
            return (string) (new Parser())->parseFile($pdfPath)->getText();
        } catch (\Throwable $e) {
            Log::warning('Invoice embedded PDF text extraction failed', ['path' => $pdfPath, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * OCR every page of a PDF and return the raw text.
     *
     * Shares the same Ghostscript + Tesseract toolchain as extractInvoiceData(),
     * so it works anywhere invoice OCR works (e.g. the production VPS).
     */
    public function extractRawText(string $pdfPath, ?int $lastPage = null, int $dpi = 300): string
    {
        $dpi = max(150, min($dpi, 300));
        $key = $this->ocrMemoKey($pdfPath, $lastPage, $dpi);
        if ($key !== null && array_key_exists($key, self::$ocrMemo)) {
            return self::$ocrMemo[$key];
        }

        $text = '';
        try {
            $tempDir = sys_get_temp_dir();
            $base = uniqid('pdf_ocr_');
            $imagePattern = $tempDir . DIRECTORY_SEPARATOR . $base . '-%d.png';

            $pageRange = $lastPage === null ? '' : ' -dFirstPage=1 -dLastPage=' . max(1, $lastPage);
            $gsCmd = "{$this->config['ghostscript']} -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r{$dpi}{$pageRange} -sOutputFile=\"{$imagePattern}\" \"{$pdfPath}\" 2>&1";
            $gsOutput = (string) @shell_exec($gsCmd);
            $images = glob($tempDir . DIRECTORY_SEPARATOR . $base . '-*.png') ?: [];
            Log::info('Ghostscript PDF render completed', [
                'path' => $pdfPath,
                'last_page' => $lastPage,
                'dpi' => $dpi,
                'images_rendered' => count($images),
            ]);
            if (!$images) {
                Log::warning('Ghostscript produced no OCR images', ['path' => $pdfPath, 'output' => mb_substr($gsOutput, 0, 1000)]);
            }

            foreach ($images as $image) {
                $outBase = $tempDir . DIRECTORY_SEPARATOR . pathinfo($image, PATHINFO_FILENAME);
                $tessOutput = (string) @shell_exec("{$this->config['tesseract']} \"{$image}\" \"{$outBase}\" -l eng 2>&1");
                $textFile = $outBase . '.txt';
                if (file_exists($textFile)) {
                    $text .= file_get_contents($textFile) . "\n";
                    @unlink($textFile);
                } else {
                    Log::warning('Tesseract produced no OCR text', ['path' => $pdfPath, 'output' => mb_substr($tessOutput, 0, 1000)]);
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
        Log::info('Raw PDF OCR completed', ['path' => $pdfPath, 'last_page' => $lastPage, 'dpi' => $dpi, 'length' => mb_strlen(trim($text))]);
        return $text;
    }

    /**
     * OCR every page of a PDF and return the raw text keyed by page number (1, 2, ...).
     *
     * @return array<int, string>
     */
    public function extractRawTextByPage(string $pdfPath, ?int $lastPage = null, int $dpi = 300): array
    {
        $dpi = max(150, min($dpi, 300));
        $pages = [];
        try {
            $tempDir = sys_get_temp_dir();
            $base = uniqid('pdf_ocr_p_');
            $imagePattern = $tempDir . DIRECTORY_SEPARATOR . $base . '-%d.png';

            $pageRange = $lastPage === null ? '' : ' -dFirstPage=1 -dLastPage=' . max(1, $lastPage);
            $gsCmd = "{$this->config['ghostscript']} -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r{$dpi}{$pageRange} -sOutputFile=\"{$imagePattern}\" \"{$pdfPath}\" 2>&1";
            $gsOutput = (string) @shell_exec($gsCmd);
            $images = glob($tempDir . DIRECTORY_SEPARATOR . $base . '-*.png') ?: [];
            natsort($images);

            Log::info('Ghostscript PDF render completed for page extraction', [
                'path' => $pdfPath,
                'last_page' => $lastPage,
                'dpi' => $dpi,
                'images_rendered' => count($images),
            ]);

            foreach ($images as $image) {
                $pageNo = count($pages) + 1;
                if (preg_match('/-(\d+)\.png$/', $image, $m)) {
                    $pageNo = (int) $m[1];
                }

                $outBase = $tempDir . DIRECTORY_SEPARATOR . pathinfo($image, PATHINFO_FILENAME);
                $tessOutput = (string) @shell_exec("{$this->config['tesseract']} \"{$image}\" \"{$outBase}\" -l eng 2>&1");
                $textFile = $outBase . '.txt';
                $pageText = '';
                if (file_exists($textFile)) {
                    $pageText = (string) file_get_contents($textFile);
                    @unlink($textFile);
                } else {
                    Log::warning('Tesseract produced no OCR text for page ' . $pageNo, ['path' => $pdfPath, 'output' => mb_substr($tessOutput, 0, 1000)]);
                }
                $pages[$pageNo] = $pageText;
                @unlink($image);
            }
        } catch (\Throwable $e) {
            Log::error('Bank PDF OCR by page failed', ['error' => $e->getMessage()]);
        }

        return $pages;
    }

    /**
     * Identity of a file for memoisation: real path plus size and mtime, so a
     * changed file is never served from a stale entry.
     */
    private function ocrMemoKey(string $pdfPath, ?int $lastPage = null, int $dpi = 300): ?string
    {
        $real = realpath($pdfPath);
        if ($real === false || !is_file($real)) {
            return null;
        }
        $stat = @stat($real);
        return $real . '|' . ($stat['size'] ?? 0) . '|' . ($stat['mtime'] ?? 0) . '|' . ($lastPage ?? 'all') . '|' . $dpi;
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
            'net_amount'    => $this->extractValue($text, ['amount excl(?:uding)?(?: vat)?', 'subtotal(?: excl(?:uding)? vat)?', 'goods value', 'goods total', 'sub total', 'order value', 'net amount', '\bnet\b']),
            'vat_amount'    => $this->extractValue($text, ['vat value', 'total vat', 'vat total', 'vat amount', 'value added tax', 'sales tax', '\bvat\b', '\btax\b']),
            'total_amount'  => $this->extractValue($text, ['invoice total', 'total due', 'grand total', 'total paid', 'amount paid', 'amount due', 'amount charged', 'payment total', 'payment amount', 'balance due', 'goods supplied', 'outstanding', '\btotal\b']),
            'vat_percent'   => $this->extractPercent($text),
            'tax_percent'   => $this->extractPercent($text),
        ];

        return $data;
    }

    /**
     * Make the three money legs agree.
     *
     * Receipts print "VAT 20%" and OCR happily returns 20.00 as the VAT amount,
     * and some PDFs only print two of net / VAT / gross. Rebuild the missing or
     * mis-read leg so the receipt form never gets nonsense.
     */
    private function reconcileAmounts(array $data): array
    {
        $toFloat = static function ($value) {
            if ($value === null || $value === '') {
                return null;
            }
            return is_numeric($value) ? (float) $value : null;
        };

        $net   = $toFloat($data['net_amount'] ?? null);
        $vat   = $toFloat($data['vat_amount'] ?? null);
        $total = $toFloat($data['total_amount'] ?? null);
        $pct   = $toFloat($data['vat_percent'] ?? null);

        // "VAT 20%" captured as the VAT amount: drop it and use the rate instead.
        if ($vat !== null && $pct !== null && abs($vat - $pct) < 0.005) {
            $vat = null;
        }
        if ($vat === null && $net !== null && $pct !== null && $pct > 0) {
            $vat = round($net * $pct / 100, 2);
        }

        // Fill in whichever leg the document did not print.
        if ($vat === null && $net !== null && $total !== null) {
            $vat = round($total - $net, 2);
        }
        if ($net === null && $vat !== null && $total !== null) {
            $net = round($total - $vat, 2);
        }
        if ($total === null && $net !== null && $vat !== null) {
            $total = round($net + $vat, 2);
        }

        // A VAT amount larger than the gross is always a bad read.
        if ($vat !== null && $total !== null && $vat > $total) {
            $vat = round($total - ($net ?? 0), 2);
            if ($vat < 0) {
                $vat = 0.0;
            }
        }

        if ($net !== null) {
            $data['net_amount'] = number_format($net, 2, '.', '');
        }
        if ($vat !== null) {
            $data['vat_amount'] = number_format($vat, 2, '.', '');
        }
        if ($total !== null) {
            $data['total_amount'] = number_format($total, 2, '.', '');
        }

        return $data;
    }

        /**
     * Helper function to find numeric value based on keywords
     */
    private function extractValue(string $text, array $keywords): ?string
    {
        foreach ($keywords as $keyword) {
            $amount = '(\d+(?:,\d{3})*(?:\.\d{2})?)';
            // Most documents use "Total: 12.34", but receipt printers and
            // OCR frequently emit the value first: "12.34 Total".
            $pattern = '/' . $keyword . '[^\d]{0,50}?' . $amount . '/i';
            if (preg_match($pattern, $text, $matches)) {
                return str_replace(',', '', $matches[1]);
            }

            $reversePattern = '/' . $amount . '[^\d]{0,50}?' . $keyword . '/i';
            if (preg_match($reversePattern, $text, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /** Log only the relevant OCR lines when a receipt needs a new parser rule. */
    private function logUndetectedInvoiceMoneyLines(string $path, string $text): void
    {
        $lines = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
            if ($line !== '' && preg_match('/(?:total|amount|paid|due|charge|vat|tax|subtotal|balance)/iu', $line)
                && preg_match('/\d+(?:,\d{3})*(?:\.\d{2})?/', $line)) {
                $lines[] = mb_substr($line, 0, 200);
            }
            if (count($lines) >= 12) {
                break;
            }
        }
        Log::warning('Invoice OCR found no recognised fields', [
            'path' => $path,
            'money_line_candidates' => $lines,
            'candidate_count' => count($lines),
        ]);
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
