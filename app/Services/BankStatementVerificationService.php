<?php

namespace App\Services;

use App\Models\BankImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;

/**
 * Statement verification engine (HD_Bank_Statement_OCR_Verification_Guide).
 *
 * Extracting text from a bank statement does not prove every transaction was
 * captured, so this service checks page coverage, parsed-row validity, running
 * balances, opening/closing balance maths, duplicates and date gaps, records the
 * exceptions, and decides whether the statement may be sent to reconciliation.
 */
class BankStatementVerificationService
{
    public const PENDING = 'pending';
    public const READY = 'ready_for_reconciliation';
    public const REVIEW = 'needs_accountant_review';
    public const FAILED = 'extraction_failed';
    public const VERIFIED = 'verified';

    /** Exceptions this service regenerates on every check (kept out of the audit trail). */
    private const AUTO_CODES = [
        'unparsed_transaction_rows',
        'page_without_rows',
        'balance_mismatch',
        'balance_not_detected',
        'running_balance_mismatch',
        'possible_duplicate',
        'statement_period_gap',
        'no_transactions_extracted',
        'ocr_no_page_mapping',
    ];

    private const DATE_LINE = '/^(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{1,2}\s+[A-Za-z]{3,9}\s+\d{4})\b\s*(.*)$/u';

    private const DATE_FORMATS = ['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'd M Y', 'j M Y', 'd F Y', 'j F Y'];

    /* ------------------------------------------------------------------ *
     * Extraction
     * ------------------------------------------------------------------ */

    /**
     * @return array{pages: array<int,array{text_length:int,transactions_detected:bool,rows:int}>,
     *               rows: array<int,array<string,mixed>>, unparsed:int, unparsed_samples:array<int,string>,
     *               opening_balance:?float, closing_balance:?float, ocr:bool, pages_total:int, text:string}
     */
    public function analyse(string $path): array
    {
        $parsedPages = [];
        try {
            foreach ((new Parser())->parseFile($path)->getPages() as $index => $page) {
                $parsedPages[$index + 1] = (string) $page->getText();
            }
        } catch (\Throwable $e) {
            $parsedPages = [];
        }

        $pagesTotal = max(1, count($parsedPages));
        $embedded = trim(implode("\n", $parsedPages));
        $ocr = false;
        $ocrReason = null;

        // Full-document OCR is deliberately reserved for image-only PDFs. A
        // four-page OCR can take tens of seconds and must not turn a usable
        // text-based statement into an HTTP timeout just because its layout
        // needs a bank-specific parser.
        if (mb_strlen($embedded) < 50) {
            $ocrReason = 'embedded_text_too_short';
            Log::info('Bank statement OCR fallback requested', [
                'path' => $path,
                'pages_total' => $pagesTotal,
                'embedded_length' => mb_strlen($embedded),
                'reason' => $ocrReason,
            ]);
            $ocrText = trim((string) app(InvoiceExtractorService::class)->extractRawText($path));
            if (mb_strlen($ocrText) > mb_strlen($embedded)) {
                $ocr = true;
                // OCR returns one blob for the whole document: page mapping is lost,
                // which is exactly why scanned statements always need a human check.
                $parsedPages = [1 => $ocrText];
            }
        }
        if (!$parsedPages) {
            $parsedPages = [1 => ''];
        }

        $rows = [];
        $unparsed = 0;
        $samples = [];
        $pages = [];
        foreach ($parsedPages as $pageNo => $text) {
            $parsed = $this->parsePage($text, (int) $pageNo, $ocr);
            $rows = array_merge($rows, $parsed['rows']);
            $unparsed += $parsed['unparsed'];
            $samples = array_merge($samples, $parsed['samples']);
            $pages[(int) $pageNo] = [
                'text_length' => mb_strlen(trim($text)),
                'transactions_detected' => $this->looksLikeTransactions($text),
                'rows' => count($parsed['rows']),
                'ocr_unmapped' => $ocr,
            ];
        }

        // OCR returns one blob for the whole document, so every real PDF page must still
        // appear in the coverage table — marked as unmapped instead of "no transactions".
        if ($ocr && $pagesTotal > count($pages)) {
            for ($page = 1; $page <= $pagesTotal; $page++) {
                if (!isset($pages[$page])) {
                    $pages[$page] = ['text_length' => 0, 'transactions_detected' => false, 'rows' => 0, 'ocr_unmapped' => true];
                }
            }
            ksort($pages);
        }

        $text = implode("\n", $parsedPages);
        $opening = $this->figure($text, ['opening balance', 'balance brought forward', 'brought forward', 'starting balance']);
        $closing = $this->figure($text, ['closing balance', 'balance carried forward', 'carried forward', 'new balance']);

        // Many statements (Monzo, Starling) print a balance on every row but never label
        // the opening/closing balances: derive them from the ledger so the maths still holds.
        $derivedOpen = false; $derivedClose = false;
        $derivedOpening = null; $derivedClosing = null;
        if (($opening === null || $closing === null) && $rows !== []) {
            $running = 0.0;
            $derivedOpening = null;
            $derivedClosing = null;
            $derivedOpen = $opening === null;
            $derivedClose = $closing === null;
            foreach ($this->chronological($rows) as $row) {
                $running = round($running + (float) $row['amount'], 2);
                if ($row['balance_after'] !== null) {
                    if ($derivedOpening === null) {
                        $derivedOpening = round((float) $row['balance_after'] - $running, 2);
                    }
                    $derivedClosing = (float) $row['balance_after'];
                }
            }
            $opening ??= $derivedOpening;
            $closing ??= $derivedClosing;
        }

        $result = [
            'pages' => $pages,
            'rows' => $rows,
            'unparsed' => $unparsed,
            'unparsed_samples' => array_slice($samples, 0, 5),
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'opening_balance_source' => $opening === null ? 'none' : ($derivedOpen ? 'derived' : 'printed'),
            'closing_balance_source' => $closing === null ? 'none' : ($derivedClose ? 'derived' : 'printed'),
            'derived_opening_balance' => $derivedOpening,
            'derived_closing_balance' => $derivedClosing,
            'balances_derived' => $derivedOpen || $derivedClose,
            'ocr' => $ocr,
            'pages_total' => $pagesTotal,
            'text' => $text,
        ];
        Log::info('Bank statement analysis completed', [
            'path' => $path,
            'pages_total' => $pagesTotal,
            'page_results' => array_map(fn($page) => [
                'text_length' => $page['text_length'],
                'transactions_detected' => $page['transactions_detected'],
                'rows' => $page['rows'],
                'ocr_unmapped' => $page['ocr_unmapped'],
            ], $pages),
            'rows_extracted' => count($rows),
            'unparsed_rows' => $unparsed,
            'ocr_used' => $ocr,
            'ocr_reason' => $ocrReason,
            'opening_balance_found' => $opening !== null,
            'closing_balance_found' => $closing !== null,
        ]);
        return $result;
    }

    /** @return array{rows: array<int,array<string,mixed>>, unparsed:int, samples:array<int,string>} */
    private function parsePage(string $text, int $pageNo, bool $ocr): array
    {
        $rows = [];
        $unparsed = 0;
        $samples = [];
        $position = 0;
        $pendingDate = null;
        $pendingDescription = '';
        $pendingLines = 0;

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', (string) $line) ?? '');
            if ($line === '') {
                continue;
            }

            // Monzo and others glue the date to the description: "30/04/2025Joseph Buckle ...".
            if (preg_match('/^(\d{2}[\/\-]\d{2}[\/\-]\d{4})(?=[^\d\s])/u', $line, $glued)) {
                $line = $glued[1] . ' ' . mb_substr($line, mb_strlen($glued[1]));
            }

            if (preg_match(self::DATE_LINE, $line, $m) && ($date = $this->date($m[1]))) {
                $tail = trim($m[2]);

                // "01/04/2025 - 30/04/2025" is the statement period, not a transaction.
                if ($tail !== '' && preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}/', $tail)) {
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }

                $split = $tail === '' ? null : $this->splitAmounts($tail);
                if ($split !== null) {
                    [$description, $amount, $balance] = $split;
                    $rows[] = $this->statementRow($date, $description, $amount, $balance, $pageNo, ++$position, $ocr);
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }

                // Only a bare date or a plain description starts a wrapped entry; monetary
                // summary text ("£26.07 Business Account balance") must never become a row.
                if ($tail === '' || (preg_match('/[A-Za-z]/', $tail) && !str_contains($tail, '£'))) {
                    $pendingDate = $date;
                    $pendingDescription = $tail;
                    $pendingLines = 0;
                } else {
                    $pendingDate = null;
                    $pendingDescription = '';
                }
                continue;
            }

            if ($pendingDate instanceof Carbon) {
                $split = $this->splitAmounts($line);
                if ($split !== null) {
                    [$description, $amount, $balance] = $split;
                    $description = trim($pendingDescription . ' ' . $description);
                    $rows[] = $this->statementRow($pendingDate, $description, $amount, $balance, $pageNo, ++$position, $ocr);
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }
                // A summary/header line interrupted the entry: drop it instead of merging.
                if (str_contains($line, '£') || $pendingLines >= 3 || mb_strlen($pendingDescription) > 240) {
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }
                $pendingDescription = trim($pendingDescription . ' ' . $line);
                $pendingLines++;
                continue;
            }

            if (preg_match('/^(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{1,2}\s+[A-Za-z]{3,9}\s+\d{4})/', $line)
                && preg_match('/[+-]?\s*£?\s*[\d,]+\.\d{2}/', $line)) {
                $unparsed++;
                if (count($samples) < 5) {
                    $samples[] = mb_substr($line, 0, 200);
                }
            }
        }

        if ($pendingDate instanceof Carbon) {
            // A date with no amount before the page ended: something is missing.
            $unparsed++;
            if (count($samples) < 5) {
                $samples[] = mb_substr('Unfinished entry: ' . $pendingDescription, 0, 200);
            }
        }

        return ['rows' => $rows, 'unparsed' => $unparsed, 'samples' => $samples];
    }

    /** Newest-first statements (Monzo) are reversed so maths runs oldest → newest. */
    private function chronological(array $rows): array
    {
        if (count($rows) < 2) {
            return $rows;
        }
        $first = (string) ($rows[0]['transaction_date'] ?? '');
        $last = (string) ($rows[count($rows) - 1]['transaction_date'] ?? '');
        return $first > $last ? array_reverse($rows) : $rows;
    }

    private function statementRow(Carbon $date, string $description, float $amount, ?float $balance,
                                 int $pageNo, int $position, bool $ocr): array
    {
        return [
            'transaction_date' => $date->format('Y-m-d'),
            'description' => mb_substr($description !== '' ? $description : 'Unlabelled transaction', 0, 500),
            'reference' => null,
            'amount' => $amount,
            'balance_after' => $balance,
            'source_page' => $pageNo,
            'source_position' => $position,
            'extraction_source' => $ocr ? 'ocr' : 'text',
        ];
    }

    /**
     * Split a trailing amount (+ optional balance) from a description. An unsigned
     * amount is only trusted as money in when a balance column proves the layout is
     * a signed ledger; otherwise the line stays unparsed rather than guessing direction.
     *
     * @return array{0:string,1:float,2:?float}|null
     */
    private function splitAmounts(string $tail): ?array
    {
        if (!preg_match('/^(.*?)\s*((?:[+-]\s*)?£?\s*[\d,]+\.\d{2})(?:\s+((?:[+-]\s*)?£?\s*[\d,]+\.\d{2}))?$/u', $tail, $m)) {
            return null;
        }
        $description = trim($m[1]);
        $amountRaw = trim((string) $m[2]);
        $balanceRaw = isset($m[3]) && trim($m[3]) !== '' ? trim((string) $m[3]) : null;
        $balance = $balanceRaw !== null ? $this->amount($balanceRaw) : null;

        if (!preg_match('/^[+-]/', $amountRaw) && $balance === null) {
            return null;
        }

        return [$description, $this->amount($amountRaw), $balance];
    }

    private function looksLikeTransactions(string $text): bool
    {
        return (bool) preg_match('/^(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{1,2}\s+[A-Za-z]{3,9}\s+\d{4})\s+.+?[+-]?\s*£?\s*[\d,]+\.\d{2}/mu', $text);
    }

    private function figure(string $text, array $labels, bool $last = false): ?float
    {
        $pattern = '/(?:' . implode('|', array_map(fn ($label) => preg_quote($label, '/'), $labels)) . ')[^\d\-]{0,40}(-?[\d,]+\.\d{2})/iu';
        if (!preg_match_all($pattern, $text, $matches) || !$matches[1]) {
            return null;
        }
        $raw = $last ? end($matches[1]) : $matches[1][0];
        return $this->amount((string) $raw);
    }

    private function date(string $raw): ?Carbon
    {
        $raw = preg_replace('/\s+/u', ' ', trim($raw)) ?? '';
        foreach (self::DATE_FORMATS as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $raw);
            } catch (\Throwable $e) {
                continue;
            }
            if ($date && $date->format($format) === $raw) {
                return $date;
            }
        }
        return null;
    }

    private function amount(string $raw): float
    {
        $value = trim(str_replace(['£', ',', ' ', "\u{a0}"], '', $raw));
        $negative = false;
        if (preg_match('/^\((.*)\)$/', $value, $m)) {
            $negative = true;
            $value = $m[1];
        }
        $value = ltrim($value, '+');
        if (str_starts_with($value, '-')) {
            $negative = true;
            $value = substr($value, 1);
        }
        $number = round((float) $value, 2);
        return $negative ? -$number : $number;
    }

    /* ------------------------------------------------------------------ *
     * Persistence & checks
     * ------------------------------------------------------------------ */

    /** Analyse the stored PDF for an import and run the full check set. */
    public function refresh(BankImport $import, ?array $analysis = null): array
    {
        $analysis ??= $this->analyseStored($import);

        $entries = DB::table('bank_entries')
            ->where('bank_import_id', $import->id)
            ->orderByRaw('COALESCE(source_page, 1)')
            ->orderByRaw('COALESCE(source_position, 0)')
            ->orderBy('id')
            ->get();

        DB::table('bank_statement_exceptions')
            ->where('bank_import_id', $import->id)
            ->whereNull('resolved_at')
            ->whereIn('code', self::AUTO_CODES)
            ->delete();

        $this->storePages($import, $analysis, $entries);
        $this->flagUnparsedRows($import, $analysis);
        if (!empty($analysis['ocr'])) {
            $this->exception($import->id, [
                'code' => 'ocr_no_page_mapping',
                'severity' => 'warning',
                'message' => 'This statement was read with OCR, which loses the page/row layout. Check every row against the original PDF before verifying.',
            ]);
        }
        [$opening, $closing, $calculated, $difference] = $this->checkBalances($import, $analysis, $entries);
        $this->checkRunningBalances($import, $entries);
        $this->flagDuplicates($import, $entries);
        $this->flagDateGap($import, $entries);

        $blocking = $this->blockingCount($import);
        $status = match (true) {
            $entries->isEmpty() => self::FAILED,
            $blocking > 0 => self::REVIEW,
            $import->verified_at !== null => self::VERIFIED,
            default => self::READY,
        };

        $balanceSource = ($analysis['opening_balance_source'] ?? 'none') === 'derived' || ($analysis['closing_balance_source'] ?? 'none') === 'derived' ? 'derived' : (($analysis['opening_balance'] !== null && $analysis['closing_balance'] !== null) ? 'printed' : 'none');
        if ($balanceSource === 'derived' && $analysis['opening_balance'] === null && $analysis['closing_balance'] === null) {
            $this->exception($import->id, [
                'code' => 'derived_balances_only',
                'severity' => 'warning',
                'message' => 'Opening and closing balances were derived from extracted rows, not read from the PDF. This is not independent proof of completeness. Verify every row against the original PDF before approving.',
                'source_value' => 'derived_opening=' . ($analysis['derived_opening_balance'] ?? 'n/a') . ' derived_closing=' . ($analysis['derived_closing_balance'] ?? 'n/a'),
            ]);
        }
        DB::table('bank_imports')->where('id', $import->id)->update([
            'verification_status' => $status,
            'pages_total' => count($analysis['pages']),
            'pages_processed' => count(array_filter($analysis['pages'], fn ($page) => $page['text_length'] > 0)),
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'calculated_closing_balance' => $calculated,
            'balance_difference' => $difference,
            'balance_source' => $balanceSource,
            'processing_status' => match ($status) {
                self::FAILED => 'manual_extraction_required',
                self::VERIFIED => 'verified',
                default => 'awaiting_verification',
            },
            'updated_at' => now(),
        ]);

        if ($status === self::FAILED && !$entries->isEmpty()) {
            // Defensive: entries exist but the status fell through to failed.
            DB::table('bank_imports')->where('id', $import->id)->update(['verification_status' => self::REVIEW]);
            $status = self::REVIEW;
        }
        if ($status === self::FAILED) {
            $this->exception($import->id, [
                'code' => 'no_transactions_extracted',
                'severity' => 'blocking',
                'message' => $analysis['rows'] === []
                    ? 'No transaction rows could be extracted from this statement.'
                    : 'Extracted rows could not be stored for this statement.',
            ]);
        }

        return [
            'status' => $status,
            'blocking' => $this->blockingCount($import),
            'warning' => $this->exceptionQuery($import)->where('severity', 'warning')->count(),
            'pages_total' => count($analysis['pages']),
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'calculated_closing_balance' => $calculated,
            'balance_difference' => $difference,
        ];
    }

    private function analyseStored(BankImport $import): array
    {
        $disk = Storage::disk('local');
        if (!$import->storage_path || !$disk->exists($import->storage_path)) {
            return $this->analyse('');
        }
        return $this->analyse($disk->path($import->storage_path));
    }

    private function storePages(BankImport $import, array $analysis, $entries): void
    {
        $byPage = $entries->groupBy(fn ($entry) => (int) ($entry->source_page ?? 1));
        $pageNumbers = array_unique(array_merge(array_keys($analysis['pages']), $byPage->keys()->all()));
        sort($pageNumbers);

        $rows = [];
        foreach ($pageNumbers as $pageNo) {
            $pageNo = (int) $pageNo;
            $info = $analysis['pages'][$pageNo] ?? ['text_length' => 0, 'transactions_detected' => false];
            $count = $byPage->get($pageNo)?->count() ?? 0;
            $status = 'verified';
            $note = null;

            if (($info['ocr_unmapped'] ?? false) && $pageNo > 1) {
                $status = 'ocr';
                $note = 'Scanned page: OCR text cannot be mapped back to this page. Compare it with the PDF.';
            } elseif ($info['text_length'] === 0 && $count === 0) {
                $status = 'empty';
                $note = 'No text on this page.';
            } elseif ($count === 0 && $info['transactions_detected']) {
                $status = 'review';
                $note = 'Page appears to contain transactions but no rows were extracted.';
                $this->exception($import->id, [
                    'page_no' => $pageNo,
                    'code' => 'page_without_rows',
                    'severity' => 'blocking',
                    'message' => 'Page ' . $pageNo . ' appears to contain transactions but none were extracted.',
                ]);
            } elseif ($count === 0) {
                $status = 'no_transactions';
                $note = 'No transaction rows detected on this page.';
            }

            $rows[] = [
                'bank_import_id' => $import->id,
                'page_no' => $pageNo,
                'extracted_rows' => $count,
                'text_length' => $info['text_length'],
                'status' => $status,
                'note' => $note,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('bank_statement_pages')->where('bank_import_id', $import->id)->delete();
        if ($rows) {
            DB::table('bank_statement_pages')->insert($rows);
        }
    }

    private function flagUnparsedRows(BankImport $import, array $analysis): void
    {
        if (($analysis['unparsed'] ?? 0) < 1) {
            return;
        }
        $this->exception($import->id, [
            'code' => 'unparsed_transaction_rows',
            'severity' => 'blocking',
            'message' => $analysis['unparsed'] . ' statement line(s) looked like transactions but could not be read confidently.',
            'source_value' => implode("\n", $analysis['unparsed_samples'] ?? []),
        ]);
    }

    /** @return array{0:?float,1:?float,2:?float,3:?float} */
    private function checkBalances(BankImport $import, array $analysis, $entries): array
    {
        $opening = $analysis['opening_balance'];
        $closing = $analysis['closing_balance'];
        $sum = round($entries->sum(fn ($entry) => (float) $entry->amount), 2);
        $calculated = $opening !== null ? round($opening + $sum, 2) : null;
        $difference = ($calculated !== null && $closing !== null) ? round($calculated - $closing, 2) : null;

        if ($opening === null || $closing === null) {
            $this->exception($import->id, [
                'code' => 'balance_not_detected',
                'severity' => 'warning',
                'message' => 'Opening and/or closing balance could not be read from the PDF, so the statement total cannot be proven automatically.',
                'source_value' => 'opening=' . ($opening ?? 'n/a') . ' closing=' . ($closing ?? 'n/a'),
            ]);
        } elseif (abs($difference) >= 0.01) {
            $this->exception($import->id, [
                'code' => 'balance_mismatch',
                'severity' => 'blocking',
                'message' => 'Opening balance plus extracted transactions does not reach the PDF closing balance.',
                'source_value' => 'opening=' . number_format($opening, 2, '.', '')
                    . ' transactions=' . number_format($sum, 2, '.', '')
                    . ' calculated=' . number_format((float) $calculated, 2, '.', '')
                    . ' pdf_closing=' . number_format($closing, 2, '.', '')
                    . ' difference=' . number_format((float) $difference, 2, '.', ''),
            ]);
        }

        return [$opening, $closing, $calculated, $difference];
    }

    private function checkRunningBalances(BankImport $import, $entries): void
    {
        // Monzo prints newest first; walk the rows oldest → newest so each balance
        // can be proven from the previous row plus its signed amount.
        $rows = $entries->values()->all();
        if (count($rows) > 1 && (string) $rows[0]->transaction_date > (string) $rows[count($rows) - 1]->transaction_date) {
            $rows = array_reverse($rows);
        }

        $previous = null;
        foreach ($rows as $entry) {
            if ($entry->balance_after === null) {
                continue;
            }
            $balance = (float) $entry->balance_after;
            if ($previous !== null && abs(round($previous + (float) $entry->amount, 2) - $balance) >= 0.01) {
                $this->exception($import->id, [
                    'bank_entry_id' => $entry->id,
                    'page_no' => $entry->source_page,
                    'code' => 'running_balance_mismatch',
                    'severity' => 'blocking',
                    'message' => 'Running balance does not follow from the previous row on page ' . ($entry->source_page ?? 1) . '.',
                    'source_value' => 'previous=' . number_format($previous, 2, '.', '')
                        . ' amount=' . number_format((float) $entry->amount, 2, '.', '')
                        . ' balance=' . number_format($balance, 2, '.', ''),
                ]);
            }
            $previous = $balance;
        }
    }

    private function flagDuplicates(BankImport $import, $entries): void
    {
        $groups = $entries->groupBy(fn ($entry) => $entry->transaction_date
            . '|' . mb_strtolower(trim((string) $entry->description))
            . '|' . number_format((float) $entry->amount, 2, '.', ''));

        foreach ($groups as $key => $rows) {
            if ($rows->count() < 2) {
                continue;
            }
            foreach ($rows as $row) {
                $this->exception($import->id, [
                    'bank_entry_id' => $row->id,
                    'page_no' => $row->source_page,
                    'code' => 'possible_duplicate',
                    'severity' => 'warning',
                    'message' => 'This row appears ' . $rows->count() . ' times with the same date, description and amount. Confirm it is a genuine repeat payment.',
                    'source_value' => $key,
                ]);
            }
        }
    }

    private function flagDateGap(BankImport $import, $entries): void
    {
        $dates = $entries->pluck('transaction_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->startOfDay())
            ->sort()
            ->values();

        if ($dates->count() < 3) {
            return;
        }
        $span = $dates->first()->diffInDays($dates->last());
        if ($span < 45) {
            return;
        }
        for ($i = 1; $i < $dates->count(); $i++) {
            if ($dates->get($i - 1)->diffInDays($dates->get($i)) > 35) {
                $this->exception($import->id, [
                    'code' => 'statement_period_gap',
                    'severity' => 'warning',
                    'message' => 'There is a gap of more than 35 days between statement rows (' . $dates->get($i - 1)->toDateString() . ' to ' . $dates->get($i)->toDateString() . '). Check no page is missing.',
                ]);
                return;
            }
        }
    }

    /* ------------------------------------------------------------------ *
     * Verification gate
     * ------------------------------------------------------------------ */

    public function verify(BankImport $import, int $actorId, ?string $note = null): array
    {
        $blocking = $this->blockingCount($import);
        if ($blocking > 0) {
            throw ValidationException::withMessages([
                'verification' => $blocking . ' blocking statement exception(s) must be resolved before this statement can be verified.',
            ]);
        }
        $unackWarnings = DB::table('bank_statement_exceptions')
            ->where('bank_import_id', $import->id)->where('severity', 'warning')
            ->whereNull('acknowledged_at')->count();
        if ($unackWarnings > 0) {
            throw ValidationException::withMessages([
                'verification' => $unackWarnings . ' warning(s) remain unacknowledged. Acknowledge each warning (with a note) before verifying.',
            ]);
        }
        // OCR or derived balances require full row-level verification.
        // OCR provenance is independent of whether an accountant acknowledged its warning.
        // Acknowledgement must never bypass mandatory row-by-row verification.
        $ocrUsed = DB::table('bank_statement_exceptions')
            ->where('bank_import_id', $import->id)
            ->where('code', 'ocr_no_page_mapping')
            ->exists();
        $derivedOnly = DB::table('bank_imports')->where('id', $import->id)->value('balance_source') === 'derived';
        if (($ocrUsed || $derivedOnly) && $import->verification_status !== self::VERIFIED) {
            $verifiedRows = DB::table('bank_entries')->where('bank_import_id', $import->id)->whereNotNull('verified_at')->count();
            $totalRows = DB::table('bank_entries')->where('bank_import_id', $import->id)->count();
            if ($verifiedRows < $totalRows) {
                throw ValidationException::withMessages([
                    'verification' => 'This statement requires full row-level verification (OCR or derived balances). Verify all ' . $totalRows . ' rows before verifying.',
                ]);
            }
        }
        $rows = DB::table('bank_entries')->where('bank_import_id', $import->id)->count();
        if ($rows === 0) {
            throw ValidationException::withMessages([
                'verification' => 'This statement has no extracted rows, so it cannot be verified.',
            ]);
        }

        DB::table('bank_imports')->where('id', $import->id)->update([
            'verification_status' => self::VERIFIED,
            'processing_status' => 'verified',
            'verified_by' => $actorId,
            'verified_at' => now(),
            'verification_note' => $note,
            'updated_at' => now(),
        ]);

        return ['status' => self::VERIFIED, 'rows' => $rows];
    }

    /** Statement edits reopen verification: the accountant must confirm the corrected rows again. */
    public function reopen(BankImport $import): void
    {
        DB::table('bank_imports')->where('id', $import->id)->update([
            'verification_status' => self::REVIEW,
            'processing_status' => 'awaiting_verification',
            'verified_by' => null,
            'verified_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function blockingCount(BankImport $import): int
    {
        return $this->exceptionQuery($import)->where('severity', 'blocking')->count();
    }

    public function exceptionQuery(BankImport $import)
    {
        return DB::table('bank_statement_exceptions')
            ->where('bank_import_id', $import->id)
            ->whereNull('resolved_at');
    }

    private function exception(int $importId, array $data): void
    {
        DB::table('bank_statement_exceptions')->insert(array_merge([
            'bank_import_id' => $importId,
            'bank_entry_id' => null,
            'page_no' => null,
            'severity' => 'blocking',
            'source_value' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $data));
    }

    public function isVerified(BankImport $import): bool
    {
        return $import->verification_status === self::VERIFIED;
    }
}
