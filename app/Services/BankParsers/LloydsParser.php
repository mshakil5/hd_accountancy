<?php

namespace App\Services\BankParsers;

class LloydsParser extends AbstractStatementParser
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    private const TYPES = ['DEB', 'SO', 'DD', 'TFR', 'FPI', 'FPO', 'CPT', 'PAY', 'DEP', 'BGC', 'CHQ', 'BP', 'COR',
        'MPI', 'MPO', 'CHG', 'FEE', 'CR', 'DR', 'ATM', 'CSH', 'INT', 'SUR', 'TFI', 'TFO', 'CIE', 'DIV', 'POS'];

    private const CREDIT_TYPES = ['FPI', 'BGC', 'DEP', 'CR', 'COR', 'MPI', 'DIV', 'TFI'];
    private const DEBIT_TYPES = ['DEB', 'SO', 'DD', 'FPO', 'CPT', 'CHQ', 'BP', 'MPO', 'CHG', 'FEE', 'DR', 'ATM', 'CSH', 'TFO', 'POS'];

    private const ROW = '/^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{2}|\d{4})\s+(.*)$/u';

    private const NOISE = [
        '/^statement no\.?/i', '/^sort code\b/i', '/^account number\b/i', '/^page \d+ of \d+$/i',
        '/^\d{1,2} [A-Za-z]{4,9} \d{4}\b/', '/^club lloyds$/i', '/^your transactions$/i',
        '/^lloyds bank plc/i', '/^date\b.*\b(pmnt|details|money|balance)/i', '/^pmnt$/i', '/^type$/i',
        '/^details\b/i', '/^money (out|in)/i', '/^balance \(£\)/i', '/^\(continued on next page\)$/i',
        '/^\d{2}-\d{2}-\d{2}$/', '/^\d{6,10}$/', '/^(statement|account) /i', '/lloyds\s*bank|pmnt|money\s*(in|out)|mon\w{0,3}y\s*(in|qut|out)|balance\s*\(/i',
    ];

    public function name(): string
    {
        return 'Lloyds / Club Lloyds';
    }

    public function canHandle(string $text, array $meta = []): bool
    {
        $h = mb_strtolower(mb_substr($text, 0, 8000));
        return (bool) (preg_match('/\blloyds\b/u', $h)
            || preg_match('/\bloydgb\b|\bgb\d{2}\s*loyd\b/u', $h)
            || (preg_match('/\b30[-\s]?\d{2}[-\s]?\d{2}\b/', $h) && preg_match('/pmnt\s*type/u', $h)));
    }

    public function parseDocument(array $pages, bool $fromOcr = false): array
    {
        $raw = [];
        $unparsed = 0;
        $samples = [];
        $opening = $closing = $totalOut = $totalIn = null;
        $cur = null;
        $done = false;

        $flush = function () use (&$cur, &$raw, &$unparsed, &$samples, &$opening, &$closing, &$totalOut, &$totalIn) {
            if ($cur === null) {
                return;
            }
            $this->finishRecord($cur, $raw, $unparsed, $samples, $opening, $closing, $totalOut, $totalIn);
            $cur = null;
        };

        foreach ($pages as $pageNo => $text) {
            foreach (preg_split('/\R/u', (string) $text) ?: [] as $rawLine) {
                if ($done) {
                    break 2;
                }
                $line = $this->fixAmountTokens($this->ocrFix($this->clean((string) $rawLine)));
                if ($line === '') {
                    continue;
                }

                if (preg_match(self::ROW, $line, $m) && isset(self::MONTHS[strtolower($m[2])])
                    && ($this->isTypedRest($m[4]) || $this->isTypelessRow($m[4])
                        || preg_match('/^STATEMENT\s*(OPENING|CLOSING)\s*BALANCE/iu', $m[4]))) {
                    $flush();
                    $cur = ['page' => (int) $pageNo, 'date' => $this->date($m[1], $m[2], $m[3]), 'rest' => $m[4]];
                    if (preg_match('/^STATEMENT\s*CLOSING\s*BALANCE/iu', $m[4])) {
                        $flush();
                        $done = true;
                    }
                    continue;
                }

                if ($cur === null || $this->isNoise($line)) {
                    continue;
                }
                $cur['rest'] .= ' ' . $line;
            }
        }
        $flush();

        $rows = [];
        foreach ($raw as $r) {
            $rows[] = $r;
        }

        $res = BalanceResolver::resolve($rows, $opening, $closing);
        $unparsed += $res['unresolved'];
        $samples = array_merge($samples, $res['samples']);

        return $this->result($this->finalise($rows, $fromOcr), $unparsed, $samples, $opening, $closing, $totalIn, $totalOut);
    }

    private function finishRecord(array $cur, array &$raw, int &$unparsed, array &$samples,
                                  ?float &$opening, ?float &$closing, ?float &$totalOut, ?float &$totalIn): void
    {
        $rest = trim($cur['rest']);

        if (preg_match('/^STATEMENT\s*OPENING\s*BALANCE\s*(.*)$/iu', $rest, $m)) {
            [, $nums] = $this->trailingMoney($m[1], 1);
            $opening = $nums[0] ?? null;
            return;
        }
        if (preg_match('/^STATEMENT\s*CLOSING\s*BALANCE\s*(.*)$/iu', $rest, $m)) {
            [, $nums] = $this->trailingMoney(preg_replace('/\s+OD$/iu', '', $m[1]), 3);
            if (count($nums) === 3) {
                [$totalOut, $totalIn, $closing] = $nums;
                if (preg_match('/\sOD$/iu', $m[1])) {
                    $closing = -abs($closing);
                }
            }
            return;
        }

        if (preg_match('/^(' . implode('|', self::TYPES) . ')\s+(.*)$/u', $rest, $m)) {
            $type = $m[1];
            $body = $m[2];
        } else {
            $type = '';
            $body = $rest;
        }
        [$desc, $nums, $od] = $this->splitMoneyRun($body);
        if (!$nums || $cur['date'] === null) {
            $this->fail($cur['rest'], $unparsed, $samples);
            return;
        }

        $balance = null;
        if (count($nums) === 2) {
            $abs = abs($nums[0]);
            $balance = $od ? -abs($nums[1]) : $nums[1];
        } else {
            $abs = abs($nums[0]);
        }

        if ($type === '') {
            $hint = preg_match('/INTEREST|REFUND|CASHBACK|CREDIT|WAIVED/i', $desc) ? 1 : 0;
        } elseif (in_array($type, self::CREDIT_TYPES, true)) {
            $hint = 1;
        } elseif (in_array($type, self::DEBIT_TYPES, true)) {
            $hint = -1;
        } else {
            $hint = 0;
        }
        $raw[] = [
            'page' => $cur['page'],
            'date' => $cur['date'],
            'desc' => trim($type . ' ' . $desc),
            'abs' => $abs,
            'balance' => $balance,
            'hint' => $hint,
            'key' => $type . '|' . mb_strtolower(preg_replace('/[^A-Za-z ]+.*$/u', '', $desc) ?? ''),
        ];
    }

    /**
     * Find the last run of 1–2 money tokens (optionally followed by "OD"); any text after it is
     * a wrapped reference line (OCR often places it below the amounts).
     * @return array{0:string,1:array<int,float>,2:bool}
     */
    private function splitMoneyRun(string $body): array
    {
        $tokens = preg_split('/\s+/u', trim($body), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $isMoney = fn (string $t) => (bool) preg_match('/^-?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}$/u', $t);
        $end = null;
        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            if ($isMoney($tokens[$i])) {
                $end = $i;
                break;
            }
        }
        if ($end === null) {
            return [trim($body), [], false];
        }
        $start = $end;
        while ($start - 1 >= 0 && $end - $start < 1 && $isMoney($tokens[$start - 1])) {
            $start--;
        }
        $od = isset($tokens[$end + 1]) && strtoupper($tokens[$end + 1]) === 'OD';
        $nums = [];
        for ($i = $start; $i <= $end; $i++) {
            $nums[] = $this->toFloat($tokens[$i]);
        }
        $descTokens = array_merge(array_slice($tokens, 0, $start), array_slice($tokens, $end + 1 + ($od ? 1 : 0)));
        return [implode(' ', $descTokens), $nums, $od];
    }

    /** Rows such as "02 Jan 26 INTEREST (GROSS) 0.30 106.87" carry no payment type. */
    /** Tesseract artefacts: table borders, glued dates, O/0, decimal commas. */
    private function ocrFix(string $line): string
    {
        $line = preg_replace('/[|{}\[\]]+/u', ' ', $line) ?? $line;
        $line = preg_replace('/(?<=[A-Z])_-?(?=\s|$)/u', ' ', $line) ?? $line;
        $line = preg_replace('/\s+/u', ' ', trim($line)) ?? $line;
        $line = preg_replace_callback('/^([0-9OolI]{1,2})\s?([A-Za-z]{3})\s?([0-9OolI]{2}|[0-9OolI]{4})(?=\s|$)/u',
            fn ($m) => strtr($m[1], 'OolI', '0011') . ' ' . $m[2] . ' ' . strtr($m[3], 'OolI', '0011'), $line) ?? $line;
        $line = preg_replace('/^(\d{2} [A-Za-z]{3} \d{2,4}) ([A-Z]{2,4})[_\-~.:=]+(?=\s)/u', '$1 $2', $line) ?? $line;
        $line = preg_replace('/(?<=\d),(\d{2})(?=\s|$)/u', '.$1', $line) ?? $line;
        return $line;
    }

    private function isTypelessRow(string $rest): bool
    {
        return (bool) preg_match('/^[A-Z][A-Z \(\)&\.\-\/]{3,}\s+-?[\d,]+\.\d{2}(?:\s+-?[\d,]+\.\d{2})?(?:\s+OD)?$/u', $rest);
    }

    private function isTypedRest(string $rest): bool
    {
        return (bool) preg_match('/^(' . implode('|', self::TYPES) . ')\b\s+\S/u', $rest);
    }

    private function isNoise(string $line): bool
    {
        $compact = preg_replace('/\s+/u', '', $line) ?? $line;
        foreach (self::NOISE as $re) {
            if (preg_match($re, $line) || preg_match($re, $compact)) {
                return true;
            }
        }
        return false;
    }

    private function date(string $d, string $mon, string $y): ?string
    {
        $m = self::MONTHS[strtolower($mon)] ?? null;
        if (!$m) {
            return null;
        }
        $year = (int) $y;
        if ($year < 100) {
            $year += 2000;
        }
        return checkdate($m, (int) $d, $year) ? sprintf('%04d-%02d-%02d', $year, $m, (int) $d) : null;
    }

    private function fail(string $text, int &$unparsed, array &$samples): void
    {
        $unparsed++;
        if (count($samples) < 5) {
            $samples[] = mb_substr($text, 0, 200);
        }
    }
}