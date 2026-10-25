<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

/**
 * Parser for Lloyds / Club Lloyds statements.
 *
 * Real layout observed from Taylor statements:
 *
 *   Date Pmnt Type Details                  Money Out (£)  Money In (£)  Balance (£)
 *   02 Jan 26 DEB THE RANGE CD 0643 01JAN26     1.40                     140.17
 *   02 Jan 26 SO  HD ACCOUNTANCY SER 0038      30.00                      19.73
 *   05 Jan 26 FPI FLASHLINE LO LTD …                      394.53          501.40
 *
 * Multi-line details are common (reference numbers wrap under Details).
 * "OD" may appear after balance when overdrawn.
 * Opening line: "31 Dec 25 STATEMENT OPENING BALANCE 138.77"
 */
class LloydsParser implements StatementParserInterface
{
    public function name(): string
    {
        return 'Lloyds / Club Lloyds';
    }

    public function canHandle(string $text, array $meta = []): bool
    {
        $header = mb_strtolower(mb_substr($text, 0, 6000));

        // Strong signals from the real PDFs
        if (preg_match('/\blloyds\b/iu', $header)) {
            return true;
        }
        if (preg_match('/\bclub\s+lloyds\b/iu', $header)) {
            return true;
        }
        // Sort-code family 30-xx-xx is classic Lloyds
        if (preg_match('/\b30[-\s]?\d{2}[-\s]?\d{2}\b/', $header)
            && preg_match('/money\s+out|money\s+in|pmnt\s*type/iu', $header)) {
            return true;
        }
        // IBAN / BIC
        if (preg_match('/\bLOYDGB\b/i', $header) || preg_match('/\bGB\d{2}\s*LOYD\b/i', $header)) {
            return true;
        }

        return false;
    }

    public function parse(string $text, int $pageNo = 1, bool $fromOcr = false): array
    {
        $rows = [];
        $unparsed = 0;
        $samples = [];
        $position = 0;

        // Normalise whitespace but keep line structure
        $lines = preg_split('/\R/u', $text) ?: [];
        $buffer = '';          // accumulates multi-line detail
        $pendingDate = null;
        $pendingType = null;

        foreach ($lines as $rawLine) {
            $line = trim(preg_replace('/\s+/u', ' ', (string) $rawLine) ?? '');
            if ($line === '') {
                continue;
            }

            // Skip pure headers / footers
            if ($this->isNoise($line)) {
                continue;
            }

            // Opening balance special case
            if (preg_match('/^(\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4})\s+STATEMENT\s+OPENING\s+BALANCE\s+([\d,]+\.\d{2})/iu', $line, $m)) {
                $date = $this->parseLloydsDate($m[1]);
                if ($date) {
                    $rows[] = $this->makeRow(
                        $date,
                        'STATEMENT OPENING BALANCE',
                        0.0,
                        $this->toFloat($m[2]),
                        $pageNo,
                        ++$position,
                        $fromOcr
                    );
                }
                $buffer = '';
                $pendingDate = null;
                continue;
            }

            // Main transaction pattern – date at start
            // e.g. "02 Jan 26 DEB THE RANGE CD 0643 01JAN26 1.40 140.17"
            // e.g. "05 Jan 26 FPI FLASHLINE LO LTD F 51-2025 03JAN26 394.53 501.40"
            if (preg_match('/^(\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4})\s+([A-Z]{2,4})\s+(.+)$/u', $line, $m)) {
                // Flush any previous incomplete buffer
                if ($pendingDate && $buffer !== '') {
                    $this->tryFlushBuffer($buffer, $pendingDate, $pendingType, $rows, $unparsed, $samples, $pageNo, $position, $fromOcr);
                    $position = count($rows);
                }

                $date = $this->parseLloydsDate($m[1]);
                $type = strtoupper($m[2]);
                $rest = trim($m[3]);

                // Try to split amount(s) from the end of the line
                $parsed = $this->splitLloydsAmounts($rest);
                if ($parsed !== null && $date) {
                    [$description, $moneyOut, $moneyIn, $balance] = $parsed;
                    $amount = $moneyOut > 0 ? -1 * $moneyOut : $moneyIn;
                    $fullDesc = trim($type . ' ' . $description);
                    $rows[] = $this->makeRow($date, $fullDesc, $amount, $balance, $pageNo, ++$position, $fromOcr);
                    $buffer = '';
                    $pendingDate = null;
                    $pendingType = null;
                } else {
                    // Start a multi-line entry
                    $pendingDate = $date;
                    $pendingType = $type;
                    $buffer = $rest;
                }
                continue;
            }

            // Continuation of a multi-line detail (no date at start, but we have a pending date)
            if ($pendingDate instanceof Carbon) {
                // If this line itself looks like a new dated transaction, flush first
                if (preg_match('/^\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4}\s+[A-Z]{2,4}\s+/u', $line)) {
                    $this->tryFlushBuffer($buffer, $pendingDate, $pendingType, $rows, $unparsed, $samples, $pageNo, $position, $fromOcr);
                    $position = count($rows);
                    // re-process this line as a new transaction (simple approach: prepend and continue next iteration)
                    // For safety we just treat it as unparsed and reset
                    $unparsed++;
                    if (count($samples) < 5) {
                        $samples[] = mb_substr($line, 0, 200);
                    }
                    $buffer = '';
                    $pendingDate = null;
                    $pendingType = null;
                    continue;
                }

                $buffer = trim($buffer . ' ' . $line);
                // Try to finish it now that we have more text
                $parsed = $this->splitLloydsAmounts($buffer);
                if ($parsed !== null) {
                    [$description, $moneyOut, $moneyIn, $balance] = $parsed;
                    $amount = $moneyOut > 0 ? -1 * $moneyOut : $moneyIn;
                    $fullDesc = trim(($pendingType ?? '') . ' ' . $description);
                    $rows[] = $this->makeRow($pendingDate, $fullDesc, $amount, $balance, $pageNo, ++$position, $fromOcr);
                    $buffer = '';
                    $pendingDate = null;
                    $pendingType = null;
                } elseif (mb_strlen($buffer) > 350) {
                    // Give up – too long without amounts
                    $unparsed++;
                    if (count($samples) < 5) {
                        $samples[] = mb_substr($buffer, 0, 200);
                    }
                    $buffer = '';
                    $pendingDate = null;
                    $pendingType = null;
                }
                continue;
            }

            // Line that looks like a transaction but we failed to parse
            if (preg_match('/^\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4}/', $line)
                && preg_match('/[\d,]+\.\d{2}/', $line)) {
                $unparsed++;
                if (count($samples) < 5) {
                    $samples[] = mb_substr($line, 0, 200);
                }
            }
        }

        // Final flush
        if ($pendingDate && $buffer !== '') {
            $this->tryFlushBuffer($buffer, $pendingDate, $pendingType, $rows, $unparsed, $samples, $pageNo, $position, $fromOcr);
        }

        return ['rows' => $rows, 'unparsed' => $unparsed, 'samples' => $samples];
    }

    /* ------------------------------------------------------------------ */

    private function tryFlushBuffer(
        string $buffer,
        ?Carbon $date,
        ?string $type,
        array &$rows,
        int &$unparsed,
        array &$samples,
        int $pageNo,
        int &$position,
        bool $fromOcr
    ): void {
        if (!$date) {
            return;
        }
        $parsed = $this->splitLloydsAmounts($buffer);
        if ($parsed === null) {
            $unparsed++;
            if (count($samples) < 5) {
                $samples[] = mb_substr($buffer, 0, 200);
            }
            return;
        }
        [$description, $moneyOut, $moneyIn, $balance] = $parsed;
        $amount = $moneyOut > 0 ? -1 * $moneyOut : $moneyIn;
        $fullDesc = trim(($type ?? '') . ' ' . $description);
        $rows[] = $this->makeRow($date, $fullDesc, $amount, $balance, $pageNo, ++$position, $fromOcr);
    }

    /**
     * From the right-hand side of a Lloyds line, pull Money Out / Money In / Balance.
     * Returns [description, moneyOut, moneyIn, balance] or null.
     */
    private function splitLloydsAmounts(string $rest): ?array
    {
        // Strip trailing "OD" / "od"
        $rest = preg_replace('/\s+OD\s*$/iu', '', $rest);
        $rest = trim($rest);

        // Collect all money-looking tokens from the end
        // Pattern: optional amount, optional amount, required balance
        // Examples:
        //   "THE RANGE CD 0643 01JAN26 1.40 140.17"
        //   "FLASHLINE LO LTD F 51-2025 03JAN26 394.53 501.40"
        //   "INTEREST (GROSS) 0.30 106.87"
        //   "D TAYLOR 309443 81400168 79.46 69.01"  (transfer – money in)

        if (!preg_match('/^(.*?)\s+([\d,]+\.\d{2})(?:\s+([\d,]+\.\d{2}))?(?:\s+([\d,]+\.\d{2}))?\s*$/u', $rest, $m)) {
            return null;
        }

        $description = trim($m[1]);
        $nums = array_values(array_filter([
            isset($m[2]) ? $this->toFloat($m[2]) : null,
            isset($m[3]) ? $this->toFloat($m[3]) : null,
            isset($m[4]) ? $this->toFloat($m[4]) : null,
        ], fn ($v) => $v !== null));

        if (count($nums) === 0) {
            return null;
        }

        // Heuristic:
        // - 1 number  → treat as balance only (rare) → amount 0
        // - 2 numbers → amount + balance.  Decide direction later by type, default money-out
        // - 3 numbers → money-out, money-in, balance
        $moneyOut = 0.0;
        $moneyIn  = 0.0;
        $balance  = null;

        if (count($nums) === 1) {
            $balance = $nums[0];
        } elseif (count($nums) === 2) {
            // Most common: amount + balance
            // We still don't know direction from numbers alone; caller will use payment type.
            // For now put it in moneyOut; the outer code can flip based on type if needed.
            // Better: if the description contains typical credit keywords, treat as money-in.
            $amount = $nums[0];
            $balance = $nums[1];
            if ($this->looksLikeCredit($description)) {
                $moneyIn = $amount;
            } else {
                $moneyOut = $amount;
            }
        } else {
            // 3 numbers → out, in, balance
            $moneyOut = $nums[0];
            $moneyIn  = $nums[1];
            $balance  = $nums[2];
        }

        // Clean description of trailing date-like tokens that sometimes stick
        $description = preg_replace('/\s+\d{1,2}[A-Z]{3}\d{2}\s*$/i', '', $description);
        $description = trim($description);

        if ($description === '') {
            $description = 'Unlabelled transaction';
        }

        return [$description, $moneyOut, $moneyIn, $balance];
    }

    private function looksLikeCredit(string $desc): bool
    {
        $d = mb_strtolower($desc);
        $creditHints = [
            'fpi', 'bgc', 'dep', 'credit', 'interest', 'waived',
            'salary', 'pension', 'refund', 'transfer from', 'tfr from',
        ];
        foreach ($creditHints as $h) {
            if (str_contains($d, $h)) {
                return true;
            }
        }
        // Payment type prefixes that are credits
        if (preg_match('/^(fpi|bgc|dep|so\s+from)\b/i', $desc)) {
            return true;
        }
        return false;
    }

    private function parseLloydsDate(string $raw): ?Carbon
    {
        $raw = trim($raw);
        // "02 Jan 26" or "02 Jan 2026"
        $formats = ['d M y', 'd M Y', 'j M y', 'j M Y'];
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $raw);
                if ($dt !== false) {
                    // 2-digit year: assume 2000+
                    if ($dt->year < 100) {
                        $dt->year = 2000 + $dt->year;
                    }
                    return $dt->startOfDay();
                }
            } catch (\Throwable $e) {
                // continue
            }
        }
        return null;
    }

    private function toFloat(string $raw): float
    {
        return (float) str_replace([',', ' '], '', $raw);
    }

    private function isNoise(string $line): bool
    {
        $l = mb_strtolower($line);
        $noise = [
            'statement no.', 'sort code', 'account number', 'page ',
            'your transactions', 'money out', 'money in', 'balance (£)',
            'pmnt type', 'continued on next page', 'lloyds bank plc',
            'turn over', 'things you need to know', 'getting in touch',
        ];
        foreach ($noise as $n) {
            if (str_contains($l, $n)) {
                return true;
            }
        }
        // Very short lines that are just page numbers etc.
        if (mb_strlen($line) < 4) {
            return true;
        }
        return false;
    }

    private function makeRow(
        Carbon $date,
        string $description,
        float $amount,
        ?float $balance,
        int $pageNo,
        int $position,
        bool $fromOcr
    ): array {
        return [
            'transaction_date' => $date->format('Y-m-d'),
            'description'      => mb_substr($description, 0, 500),
            'reference'        => null,
            'amount'           => round($amount, 2),
            'balance_after'    => $balance !== null ? round($balance, 2) : null,
            'source_page'      => $pageNo,
            'position'         => $position,
            'from_ocr'         => $fromOcr,
        ];
    }
}
