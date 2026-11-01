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
            'pdftotext' => $this->findBinary(
                [(string) getenv('PDFTOTEXT_BIN')],
                PHP_OS_FAMILY === 'Windows' ? ['pdftotext.exe'] : ['pdftotext'],
                PHP_OS_FAMILY === 'Windows'
                    ? ['C:/Program Files/poppler*/Library/bin/pdftotext.exe', 'C:/poppler*/Library/bin/pdftotext.exe']
                    : ['/usr/bin/pdftotext', '/usr/local/bin/pdftotext', '/opt/homebrew/bin/pdftotext']
            ),
            'tesseract' => $this->findBinary(
                [(string) getenv('TESSERACT_BIN')],
                ['tesseract'],
                PHP_OS_FAMILY === 'Windows'
                    ? ['C:/Program Files/Tesseract-OCR/tesseract.exe', 'C:/Program Files (x86)/Tesseract-OCR/tesseract.exe']
                    : ['/usr/bin/tesseract', '/usr/local/bin/tesseract', '/opt/homebrew/bin/tesseract']
            ),
            'convert' => $this->findBinary(
                [(string) getenv('IMAGEMAGICK_BIN')],
                ['convert', 'magick'],
                ['/usr/bin/convert', '/usr/local/bin/convert', '/opt/homebrew/bin/convert']
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
            $result['supplier'] = $this->extractSupplier($text);
            Log::info('Invoice OCR fields detected', [
                'path' => $pdfPath,
                'net_amount_found' => $result['net_amount'] !== null,
                'vat_amount_found' => $result['vat_amount'] !== null,
                'total_amount_found' => $result['total_amount'] !== null,
                'vat_percent_found' => $result['vat_percent'] !== null,
                'supplier_found' => !empty($result['supplier']),
            ]);
            // Don't treat missing supplier as "no fields"
            $moneyFields = array_filter([
                $result['net_amount'] ?? null,
                $result['vat_amount'] ?? null,
                $result['total_amount'] ?? null,
            ]);
            if (!$moneyFields) {
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
                $processed = $this->preprocessImageForOcr($image);
                $outBase = $tempDir . DIRECTORY_SEPARATOR . pathinfo($processed, PATHINFO_FILENAME);
                // psm 6 = assume uniform block of text (good for receipts)
                $tessOutput = (string) @shell_exec("{$this->config['tesseract']} \"{$processed}\" \"{$outBase}\" -l eng --psm 6 --oem 3 2>&1");
                $textFile = $outBase . '.txt';
                if (file_exists($textFile)) {
                    $text .= file_get_contents($textFile) . "\n";
                    @unlink($textFile);
                } else {
                    Log::warning('Tesseract produced no OCR text', ['path' => $pdfPath, 'output' => mb_substr($tessOutput, 0, 1000)]);
                }
                @unlink($image);
                if ($processed !== $image && file_exists($processed)) {
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

                $processed = $this->preprocessImageForOcr($image);
                $outBase = $tempDir . DIRECTORY_SEPARATOR . pathinfo($processed, PATHINFO_FILENAME);
                $tessOutput = (string) @shell_exec("{$this->config['tesseract']} \"{$processed}\" \"{$outBase}\" -l eng --psm 6 --oem 3 2>&1");
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
                if ($processed !== $image && file_exists($processed)) {
                    @unlink($processed);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Bank PDF OCR by page failed', ['error' => $e->getMessage()]);
        }

        return $pages;
    }

    /**
     * Text layer read by native tools instead of Smalot. Some bank PDFs (Lloyds) embed fonts with
     * a custom encoding that Smalot decodes as gibberish; pdftotext and Ghostscript decode them.
     * Tries pdftotext first, then Ghostscript txtwrite (always present because OCR needs it).
     *
     * @return array<int, string> page number => text
     */
    public function extractNativeTextByPage(string $pdfPath): array
    {
        $pages = [];
        try {
            $bin = $this->config['pdftotext'];
            $tmp = tempnam(sys_get_temp_dir(), 'pdftxt_');
            if ($tmp !== false) {
                @shell_exec("{$bin} -raw -enc UTF-8 \"{$pdfPath}\" \"{$tmp}\" 2>&1");
                $raw = is_file($tmp) ? (string) file_get_contents($tmp) : '';
                @unlink($tmp);
                if (trim($raw) !== '' && !str_contains($raw, 'not found')) {
                    foreach (explode("\f", $raw) as $i => $text) {
                        if (trim($text) !== '' || $i === 0) {
                            $pages[$i + 1] = $text;
                        }
                    }
                    // pdftotext ends with a trailing form feed: drop the empty last page.
                    if ($pages && trim((string) end($pages)) === '' && count($pages) > 1) {
                        array_pop($pages);
                    }
                    if (mb_strlen(trim(implode('', $pages))) > 50) {
                        return $pages;
                    }
                }
                $pages = [];
            }

            $base = tempnam(sys_get_temp_dir(), 'gstxt_');
            if ($base !== false) {
                @unlink($base);
                $pattern = $base . '-%d.txt';
                @shell_exec("{$this->config['ghostscript']} -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=txtwrite -sOutputFile=\"{$pattern}\" \"{$pdfPath}\" 2>&1");
                $files = glob($base . '-*.txt') ?: [];
                natsort($files);
                foreach ($files as $file) {
                    if (preg_match('/-(\d+)\.txt$/', $file, $m)) {
                        $pages[(int) $m[1]] = (string) file_get_contents($file);
                    }
                    @unlink($file);
                }
                ksort($pages);
            }
        } catch (\Throwable $e) {
            Log::error('Native PDF text extraction failed', ['error' => $e->getMessage()]);
            $pages = [];
        }

        return $pages;
    }

    /**
     * Layout-preserving OCR for tabular documents (bank statements).
     * Runs Tesseract in TSV mode (single uniform block, spaces preserved) and rebuilds every
     * visual row from word coordinates, so IN / OUT / BALANCE columns stay on the same line
     * as their date and description instead of being split into separate text blocks.
     *
     * @return array<int, string>
     */
    public function extractLayoutTextByPage(string $pdfPath, int $dpi = 300): array
    {
        $dpi = max(200, min($dpi, 400));
        $pages = [];
        try {
            $tempDir = sys_get_temp_dir();
            $base = uniqid('pdf_ocr_l_');
            $imagePattern = $tempDir . DIRECTORY_SEPARATOR . $base . '-%d.png';
            $gsCmd = "{$this->config['ghostscript']} -dSAFER -dBATCH -dNOPAUSE -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -sDEVICE=pnggray -r{$dpi} -sOutputFile=\"{$imagePattern}\" \"{$pdfPath}\" 2>&1";
            @shell_exec($gsCmd);
            $images = glob($tempDir . DIRECTORY_SEPARATOR . $base . '-*.png') ?: [];
            natsort($images);

            foreach ($images as $image) {
                $pageNo = count($pages) + 1;
                if (preg_match('/-(\d+)\.png$/', $image, $m)) {
                    $pageNo = (int) $m[1];
                }
                $tsv = (string) @shell_exec("{$this->config['tesseract']} \"{$image}\" stdout -l eng --oem 1 --psm 6 -c preserve_interword_spaces=1 tsv 2>/dev/null");
                $pages[$pageNo] = self::layoutTextFromTsv($tsv);
                @unlink($image);
            }
            ksort($pages);
        } catch (\Throwable $e) {
            Log::error('Bank PDF layout OCR failed', ['error' => $e->getMessage()]);
        }

        return $pages;
    }

    /** Rebuild text lines (left→right) from Tesseract TSV word boxes. */
    public static function layoutTextFromTsv(string $tsv): string
    {
        $words = [];
        foreach (preg_split('/\R/', $tsv) ?: [] as $i => $row) {
            if ($i === 0 || $row === '') {
                continue;
            }
            $c = explode("\t", $row);
            if (count($c) < 12 || (int) $c[0] !== 5) {
                continue;
            }
            $text = trim($c[11]);
            if ($text === '' || (float) $c[10] < 0) {
                continue;
            }
            $words[] = ['x' => (int) $c[6], 'y' => (int) $c[7] + (int) $c[9] / 2, 'w' => (int) $c[8], 'h' => (int) $c[9], 't' => $text];
        }
        if (!$words) {
            return '';
        }

        usort($words, fn ($a, $b) => $a['y'] <=> $b['y']);
        $heights = array_column($words, 'h');
        sort($heights);
        $tol = max(4, $heights[(int) (count($heights) / 2)] * 0.55);

        $lines = [];
        $current = [];
        $anchor = null;
        foreach ($words as $w) {
            if ($anchor !== null && abs($w['y'] - $anchor) > $tol) {
                $lines[] = $current;
                $current = [];
                $anchor = null;
            }
            $current[] = $w;
            $anchor = $anchor === null ? $w['y'] : ($anchor * (count($current) - 1) + $w['y']) / count($current);
        }
        if ($current) {
            $lines[] = $current;
        }

        $out = [];
        foreach ($lines as $line) {
            usort($line, fn ($a, $b) => $a['x'] <=> $b['x']);
            $out[] = implode(' ', array_column($line, 't'));
        }
        return implode("\n", $out);
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

    private function parseExtractedText(string $text): array
    {
        // Normalise common OCR artefacts first
        $text = $this->normaliseOcrText($text);

        // Try specialised fuel-station receipt layout first (MFG / Motor Fuel / Esso / Co-op petrol)
        $fuel = $this->parseFuelStationReceipt($text);
        if ($fuel !== null) {
            return $fuel;
        }

        // Generic invoice / receipt parsing
        $clean = preg_replace('/\s+/', ' ', $text);

        $data = [
            'net_amount'    => $this->extractValue($clean, [
                'amount excl(?:uding)?(?:\s+vat)?',
                'subtotal(?:\s+excl(?:uding)?\s+vat)?',
                'goods value', 'goods total', 'sub[\s\-]?total',
                'order value', 'net amount', 'net total', 'net value',
                'ex\.?\s*vat', 'excl\.?\s*vat', '\bnet\b',
            ]),
            'vat_amount'    => $this->extractValue($clean, [
                'vat value', 'total vat', 'vat total', 'vat amount',
                'value added tax', 'sales tax', 'vat\s*@',
            ], avoidPatterns: ['vat\s*(?:no|reg|number|registration)']),
            'total_amount'  => $this->extractValue($clean, [
                'invoice total', 'total due', 'grand total', 'total paid',
                'amount paid', 'amount due', 'amount charged',
                'payment total', 'payment amount', 'balance due',
                'goods supplied', 'outstanding', 'total inc(?:luding)?\s*vat',
                'total amount', '\btotal\b',
            ]),
            'vat_percent'   => $this->extractPercent($text),
            'tax_percent'   => $this->extractPercent($text),
        ];

        return $data;
    }

    /**
     * Clean OCR noise that commonly breaks money parsing on phone photos of receipts.
     */
    private function normaliseOcrText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Currency symbols -> space so numbers are easier to match
        $text = str_replace(['£', '€', '$', 'GBP', 'EUR'], ' ', $text);
        $text = str_replace(['V.A.T.', 'V A T', 'V.A.T'], 'VAT', $text);
        // European decimal comma between digits: 5,00 -> 5.00  (but keep 1,234.56 style)
        $text = preg_replace('/(\d),(\d{2})(?!\d)/', '$1.$2', $text);
        // OCR often drops the decimal: "Diesel 3002" when it was 30.02 – fix only in fuel context later
        // Collapse repeated punctuation / decorative lines
        $text = preg_replace('/[-=_*~.]{3,}/', ' ', $text);
        return $text;
    }

    /**
     * Parse typical UK fuel-station receipts (MFG, Motor Fuel Ltd, Esso, Co-op petrol etc.).
     * Layout usually has:
     *   Diesel|Petrol  30.02
     *   Total / VISA DELTA  30.02
     *   VAT Rate  EX.VAT  VAT
     *   20%       25.02   5.00
     *
     * Returns null when the document does not look like this style.
     */
    private function parseFuelStationReceipt(string $text): ?array
    {
        $isFuel = (bool) preg_match(
            '/\b(?:diesel|petrol|unleaded|motor\s+fuel|mfg\s+|esso|pump\s*\d|service\s+station|co-?operative)\b/i',
            $text
        );
        if (!$isFuel) {
            return null;
        }

        $total = null;
        $net   = null;
        $vat   = null;
        $pct   = null;

        // 1) Explicit total lines (prefer card / total paid)
        $totalPatterns = [
            '/(?:visa\s*delta|visa|mastercard|contactless|card)\s*[^\d]{0,15}?(\d{1,4}\.\d{2})/i',
            '/\btotal\b\s*[^\d]{0,15}?(\d{1,4}\.\d{2})/i',
            '/amount\s*(?:paid|due|charged)?\s*[^\d]{0,15}?(\d{1,4}\.\d{2})/i',
        ];
        foreach ($totalPatterns as $pat) {
            if (preg_match($pat, $text, $m)) {
                $total = $this->toMoney($m[1]);
                if ($total !== null && $total >= 1 && $total < 500) {
                    break;
                }
                $total = null;
            }
        }

        // 2) Fuel line amount (Diesel / Petrol)
        if ($total === null) {
            if (preg_match('/\b(?:diesel|petrol|unleaded)\b[^\d]{0,20}?(\d{1,4}\.\d{2})/i', $text, $m)) {
                $total = $this->toMoney($m[1]);
            }
        }

        // 3) OCR sometimes reads "30.02" as "3002" next to Diesel
        if ($total === null && preg_match('/\b(?:diesel|petrol|unleaded)\b[^\d]{0,12}?(\d{3,5})\b/i', $text, $m)) {
            $raw = $m[1];
            // e.g. 3002 -> 30.02, 2504 -> 25.04
            if (strlen($raw) === 4) {
                $candidate = (float) (substr($raw, 0, 2) . '.' . substr($raw, 2));
                if ($candidate >= 5 && $candidate <= 200) {
                    $total = $candidate;
                }
            }
        }

        // 4) VAT table: "20%  25.02  5.00" or "20% 25.02 5.00 30.02"
        if (preg_match('/(\d{1,2})\s*%\s+(\d{1,4}\.\d{2})\s+(\d{1,4}\.\d{2})(?:\s+(\d{1,4}\.\d{2}))?/', $text, $m)) {
            $pct = (float) $m[1];
            $a = $this->toMoney($m[2]);
            $b = $this->toMoney($m[3]);
            $c = isset($m[4]) ? $this->toMoney($m[4]) : null;

            // Typical order: rate, EX.VAT (net), VAT, [Inc.VAT]
            if ($a !== null && $b !== null) {
                // Prefer the pair where net + vat ≈ total (or ≈ larger number)
                if ($c !== null && abs(($a + $b) - $c) < 0.05) {
                    $net = $a;
                    $vat = $b;
                    if ($total === null) {
                        $total = $c;
                    }
                } elseif ($total !== null && abs(($a + $b) - $total) < 0.05) {
                    $net = $a;
                    $vat = $b;
                } elseif ($b < $a && $b < 50) {
                    // second number is smaller → likely VAT
                    $net = $a;
                    $vat = $b;
                } else {
                    $net = $a;
                    $vat = $b;
                }
            }
        }

        // 5) "Totals 25.02 5.00"
        if (($net === null || $vat === null) && preg_match('/totals?\s+(\d{1,4}\.\d{2})\s+(\d{1,4}\.\d{2})/i', $text, $m)) {
            $net = $net ?? $this->toMoney($m[1]);
            $vat = $vat ?? $this->toMoney($m[2]);
        }

        // 6) Infer missing legs from 20% UK fuel VAT
        if ($pct === null) {
            $pct = 20.0; // standard UK fuel VAT
        }
        if ($total !== null && $net === null && $vat === null && $pct > 0) {
            $net = round($total / (1 + $pct / 100), 2);
            $vat = round($total - $net, 2);
        }
        if ($total !== null && $net !== null && $vat === null) {
            $vat = round($total - $net, 2);
        }
        if ($total !== null && $vat !== null && $net === null) {
            $net = round($total - $vat, 2);
        }
        if ($total === null && $net !== null && $vat !== null) {
            $total = round($net + $vat, 2);
        }

        // Sanity: if we still have nothing useful, fall back to generic
        if ($total === null && $net === null) {
            return null;
        }

        return [
            'net_amount'   => $net !== null ? number_format($net, 2, '.', '') : null,
            'vat_amount'   => $vat !== null ? number_format($vat, 2, '.', '') : null,
            'total_amount' => $total !== null ? number_format($total, 2, '.', '') : null,
            'vat_percent'  => $pct !== null ? (string) $pct : null,
            'tax_percent'  => $pct !== null ? (string) $pct : null,
        ];
    }

    private function toMoney(?string $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $raw = str_replace([',', ' '], ['', ''], $raw);
        if (!is_numeric($raw)) {
            return null;
        }
        $v = (float) $raw;
        return $v >= 0 ? $v : null;
    }

    /**
     * Helper function to find numeric value based on keywords
     */
    private function extractValue(string $text, array $keywords, array $avoidPatterns = []): ?string
    {
        foreach ($keywords as $keyword) {
            $amount = '(\d{1,5}(?:,\d{3})*(?:\.\d{2})?)';
            // keyword then amount
            $pattern = '/' . $keyword . '[^\d]{0,40}?' . $amount . '/i';
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $matchedText = substr($text, max(0, $matches[0][1] - 30), 80);
                $skip = false;
                foreach ($avoidPatterns as $ap) {
                    if (preg_match('/' . $ap . '/i', $matchedText)) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $val = $this->toMoney(str_replace(',', '', $matches[1][0]));
                    if ($val !== null && $val >= 0.01 && $val < 100000) {
                        return number_format($val, 2, '.', '');
                    }
                }
            }

            // amount then keyword
            $reversePattern = '/' . $amount . '[^\d]{0,40}?' . $keyword . '/i';
            if (preg_match($reversePattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $matchedText = substr($text, max(0, $matches[0][1] - 10), 80);
                $skip = false;
                foreach ($avoidPatterns as $ap) {
                    if (preg_match('/' . $ap . '/i', $matchedText)) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $val = $this->toMoney(str_replace(',', '', $matches[1][0]));
                    if ($val !== null && $val >= 0.01 && $val < 100000) {
                        return number_format($val, 2, '.', '');
                    }
                }
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
            if ($line !== '' && preg_match('/(?:total|amount|paid|due|charge|vat|tax|subtotal|balance|diesel|petrol)/iu', $line)
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
        if (preg_match('/(?:vat|tax)\s*(?:rate|@|at)?\s*[:]*\s*(\d{1,2}(?:\.\d+)?)\s*%/i', $text, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(\d{1,2}(?:\.\d+)?)\s*%\s*(?:vat|tax)/i', $text, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(?:@|vat\s*%|tax\s*%|vat\s*rate|tax\s*rate)\s*[:]*\s*(\d+\.?\d*)\s*%/i', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Light ImageMagick pre-processing for phone photos of receipts.
     * Deskew/contrast helps Tesseract a lot; we avoid huge upscales that hit memory limits.
     */

    /**
     * Extract supplier / merchant name from receipt text.
     * Prefers legal entity names (Motor Fuel Ltd, etc.) then station header.
     */
    private function extractSupplier(string $text): ?string
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $lines = array_values(array_filter(array_map(function ($l) {
            return trim(preg_replace('/\s+/u', ' ', $l) ?? '');
        }, $lines), fn ($l) => $l !== ''));

        // High-confidence legal entity / brand patterns (anywhere in text)
        $entityPatterns = [
            '/\b(Motor\s+Fuel\s+Ltd)\b/i',
            '/\b(Motor\s+Fuel\s+Group)\b/i',
            '/\b(MFG\s+[A-Za-z][A-Za-z0-9\'\-\s]{2,40}Service\s+Station)\b/i',
            '/\b(Esso)\b/i',
            '/\b(BP\s+Express)\b/i',
            '/\b(Shell)\b/i',
            '/\b(Tesco\s+(?:Express|Extra|Petrol))\b/i',
            '/\b(Sainsbury\'?s?\s+Petrol)\b/i',
            '/\b(Asda\s+Petrol)\b/i',
            '/\b(Co-?operative)\b/i',
            '/\b(Ranet\s+1\s+Limited)\b/i',
            '/\b([A-Z][A-Za-z0-9\'\-\&\s]{2,40}\s+Ltd\.?)\b/',
            '/\b([A-Z][A-Za-z0-9\'\-\&\s]{2,40}\s+Limited)\b/',
        ];
        foreach ($entityPatterns as $pat) {
            if (preg_match($pat, $text, $m)) {
                $name = trim(preg_replace('/\s+/', ' ', $m[1]));
                // Skip if it looks like a VAT line fragment
                if (preg_match('/\b(?:VAT|Rate|Total|Inc\.?)\b/i', $name)) {
                    continue;
                }
                if (mb_strlen($name) >= 3 && mb_strlen($name) <= 60) {
                    return $name;
                }
            }
        }

        // Fallback: first non-junk line near the top (station header)
        $skip = '/^(?:tel|phone|www\.|http|store|pos|trans|op\s*name|sale|thank|please|date|mon|tue|wed|thu|fri|sat|sun|\d)/i';
        foreach (array_slice($lines, 0, 8) as $line) {
            if (preg_match($skip, $line)) {
                continue;
            }
            if (preg_match('/\d{5,}/', $line)) { // long numbers / postcodes alone
                continue;
            }
            if (preg_match('/(?:total|vat|amount|diesel|petrol|pump)/i', $line)) {
                continue;
            }
            if (mb_strlen($line) >= 4 && mb_strlen($line) <= 60) {
                return $line;
            }
        }

        return null;
    }

    private function preprocessImageForOcr(string $imagePath): string
    {
        $convert = $this->config['convert'] ?? '';
        if ($convert === '' || $convert === 'convert' || $convert === "'convert'") {
            // Still try bare name
            $convert = 'convert';
        }

        $out = $imagePath . '.prep.png';
        $cmd = "{$convert} \"{$imagePath}\" "
             . "-auto-orient -colorspace Gray "
             . "-contrast-stretch 1%x1% "
             . "-sharpen 0x0.8 "
             . "-despeckle "
             . "\"{$out}\" 2>&1";

        @shell_exec($cmd);

        if (file_exists($out) && filesize($out) > 1000) {
            return $out;
        }
        return $imagePath;
    }


}