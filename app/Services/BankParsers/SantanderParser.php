<?php

namespace App\Services\BankParsers;

class SantanderParser extends AbstractStatementParser
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    private const MON = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';
    private const MONEY = '-?£?\s?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}';

    private const NOISE = [
        '/^account name\b/i', '/^account number\b/i', '/^statement number\b/i', '/^santander$/i',
        '/^date\s+description/i', '/^\d{1,2}\s*\/\s*\d{1,2}$/', '/^(credits|debits|balance|date|description)$/i',
        '/^[\W_]{1,4}$/u',
    ];

    public function name(): string
    {
        return 'Santander';
    }

    public function canHandle(string $text, array $meta = []): bool
    {
        $h = mb_strtolower(mb_substr($text, 0, 12000));
        return (bool) (preg_match('/\bsantander\b|abbygb2l|gb\d{2}\s?abby/u', $h)
            || (preg_match('/statement number:\s*\d{3}\s*\/\s*\d{4}/u', $h)
                && preg_match('/previous statement balance|current statement balance/u', $h)));
    }

    public function parseDocument(array $pages, bool $fromOcr = false): array
    {
        $segments = [];
        $seg = null;
        $newSeg = fn (?string $num) => [
            'num' => $num, 'start' => null, 'end' => null, 'opening' => null, 'opening_prev' => false,
            'closing' => null, 'in' => null, 'out' => null, 'recs' => [], 'cur' => null, 'inTable' => false,
        ];
        $flush = function (array &$s) {
            if ($s['cur'] !== null) {
                $s['recs'][] = $s['cur'];
                $s['cur'] = null;
            }
        };

        foreach ($pages as $pageNo => $text) {
            foreach (preg_split('/\R/u', (string) $text) ?: [] as $rawLine) {
                $line = $this->fixAmountTokens($this->clean((string) $rawLine));
                $line = trim((string) preg_replace('/^[^\p{L}\p{N}£]+/u', '', $line));
                $line = preg_replace('/(?<=\s)(\d{1,3}),(\d{2})(?=\s|$)/u', '$1.$2', $line) ?? $line;
                $line = preg_replace('/(?<=\s)[§S](\d\.\d{2})(?=\s|$)/u', '5$1', $line) ?? $line;
                $line = preg_replace('/(?<=\s)(\d{1,2})(\d{2})\s+[§S$],(\d{3}\.\d{2})(?=\s|$)/u', '$1.$2 5,$3', $line) ?? $line;
                if ($line === '') {
                    continue;
                }

                if (preg_match('/statement number:?\s*(\d{2,3})\s*\/\s*(\d{4})/iu', $line, $m)) {
                    $num = $m[1] . '/' . $m[2];
                    if ($seg === null || ($seg['num'] !== null && $seg['num'] !== $num)) {
                        if ($seg !== null) {
                            $flush($seg);
                            $segments[] = $seg;
                        }
                        $seg = $newSeg($num);
                    } elseif ($seg['num'] === null) {
                        $seg['num'] = $num;
                    }
                    continue;
                }
                if ($seg === null) {
                    $seg = $newSeg(null);
                }

                // ---- summary page ----
                if (preg_match('/^your account summary for/i', $line)) {
                    $flush($seg);
                    $seg['inTable'] = false;
                    continue;
                }
                if (preg_match('/^(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{4})\s+to\s+(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{4})/u', $line, $m)
                    && isset(self::MONTHS[strtolower(substr($m[2], 0, 3))]) && isset(self::MONTHS[strtolower(substr($m[5], 0, 3))])) {
                    $seg['start'] = sprintf('%04d-%02d-%02d', $m[3], self::MONTHS[strtolower(substr($m[2], 0, 3))], $m[1]);
                    $seg['end'] = sprintf('%04d-%02d-%02d', $m[6], self::MONTHS[strtolower(substr($m[5], 0, 3))], $m[4]);
                    continue;
                }
                if (!$seg['inTable']) {
                    if (preg_match('/^balance brought forward from.*?(' . self::MONEY . ')$/iu', $line, $m)) {
                        $seg['opening'] = $this->toFloat($m[1]);
                        $seg['opening_prev'] = true;
                        continue;
                    }
                    if (preg_match('/^total credits:?\s*(' . self::MONEY . ')$/iu', $line, $m)) {
                        $seg['in'] = abs($this->toFloat($m[1]));
                        continue;
                    }
                    if (preg_match('/^total debits:?\s*(' . self::MONEY . ')$/iu', $line, $m)) {
                        $seg['out'] = abs($this->toFloat($m[1]));
                        continue;
                    }
                    if (preg_match('/^your balance at close of business.*?(' . self::MONEY . ')$/iu', $line, $m)) {
                        $seg['closing'] = $this->toFloat($m[1]);
                        continue;
                    }
                }

                // ---- table markers ----
                if (preg_match('/^date\s+description\s+credits/i', $line)) {
                    $seg['inTable'] = true;
                    continue;
                }
                if (preg_match('/^previous statement balance\s*(' . self::MONEY . ')$/iu', $line, $m)) {
                    $seg['inTable'] = true;
                    $seg['opening'] = $this->toFloat($m[1]);
                    $seg['opening_prev'] = true;
                    continue;
                }
                if (preg_match('/^brought forward balance\s*(' . self::MONEY . ')$/iu', $line, $m)) {
                    $seg['inTable'] = true;
                    $bf = $this->toFloat($m[1]);
                    if ($seg['opening'] === null && $seg['cur'] === null && $seg['recs'] === []) {
                        $seg['opening'] = $bf;
                    } elseif ($seg['cur'] !== null && $this->moneyCount($seg['cur']['rest']) === 1) {
                        // complete row cut at the page end: the carried balance belongs to it
                        $seg['cur']['rest'] .= ' ' . number_format($bf, 2, '.', '');
                    }
                    continue;
                }
                if (preg_match('/^(?:[0-9Iil|]{1,2}\s*(?:st|nd|rd|th)\s*[A-Za-z]{3}\s+)?current statement balance\s*(' . self::MONEY . ')/iu', $line, $m)) {
                    $flush($seg);
                    $seg['closing'] = $seg['closing'] ?? $this->toFloat($m[1]);
                    $seg['inTable'] = false;
                    continue;
                }
                if (!$seg['inTable']) {
                    continue;
                }

                // ---- transaction rows ----
                if (($hd = $this->rowHead($line)) !== null) {
                    $flush($seg);
                    $seg['cur'] = ['page' => (int) $pageNo, 'd' => $hd[0], 'm' => $hd[1], 'rest' => $hd[2]];
                    continue;
                }
                if ($this->isNoise($line)) {
                    continue;
                }
                if ($seg['cur'] === null) {
                    // head of this row sat on the previous (missing) page
                    $seg['cur'] = ['page' => (int) $pageNo, 'd' => null, 'm' => null, 'rest' => $line];
                    continue;
                }
                $seg['cur']['rest'] = trim($seg['cur']['rest'] . ' ' . $line);
            }
        }
        if ($seg !== null) {
            $flush($seg);
            $segments[] = $seg;
        }

        $rows = [];
        $unparsed = 0;
        $samples = [];
        $opening = $closing = null;
        $totIn = $totOut = 0.0;
        $totalsKnown = true;

        foreach ($segments as $si => $s) {
            $prevEnd = $segments[$si - 1]['end'] ?? null;
            $nextStart = $segments[$si + 1]['start'] ?? null;
            $internal = [];
            foreach ($s['recs'] as $rec) {
                $one = $this->finishRecord($rec);
                if ($one === null) {
                    $unparsed++;
                    if (count($samples) < 5) {
                        $samples[] = mb_substr(($rec['d'] ?? '?') . '/' . ($rec['m'] ?? '?') . ' ' . $rec['rest'], 0, 200);
                    }
                    continue;
                }
                $one['date'] = $rec['d'] !== null
                    ? $this->resolveDate((int) $rec['d'], (int) $rec['m'], $s['start'], $s['end'], $prevEnd, $nextStart)
                    : null;
                $internal[] = $one;
            }
            $this->fillDates($internal, $s['start'] ?? $nextStart);

            $res = BalanceResolver::resolve($internal, $s['opening'], $s['closing']);
            $unparsed += $res['unresolved'];
            $samples = array_merge($samples, $res['samples']);
            $rows = array_merge($rows, $this->finalise($internal, $fromOcr));

            $opening ??= $s['opening'];
            $closing = $s['closing'] ?? $closing;
            if ($s['in'] === null || $s['out'] === null) {
                $totalsKnown = false;
            } else {
                $totIn += $s['in'];
                $totOut += $s['out'];
            }
        }

        return $this->result($rows, $unparsed, $samples, $opening, $closing,
            $totalsKnown && $segments ? round($totIn, 2) : null,
            $totalsKnown && $segments ? round($totOut, 2) : null);
    }

    private function moneyCount(string $text): int
    {
        return preg_match_all('/(?<![\w.])-?£?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}(?![\w.])/u', $text);
    }

    private function finishRecord(array $rec): ?array
    {
        $tokens = preg_split('/\s+/u', trim($rec['rest']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $money = [];
        $desc = [];
        foreach ($tokens as $t) {
            if (preg_match('/^-?£?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}$/u', $t)) {
                $money[] = $this->toFloat($t);
            } else {
                $desc[] = $t;
            }
        }
        if (count($money) < 1 || count($money) > 2) {
            return null;
        }
        $description = trim(implode(' ', $desc));
        $hint = 0;
        if (preg_match('/^(FASTER PAYMENTS? RECEIPT|CASH PAID IN|CREDIT|INTEREST PAID|REFUND|CHEQUE DEPOSIT|DEPOSIT|BANK GIRO CREDIT|BACS CREDIT)/i', $description)
            || preg_match('/Faster Payment Safeguarding$/i', $description) && !preg_match('/^(DIRECT DEBIT|CARD|BILL|STANDING)/i', $description)) {
            $hint = 1;
        } elseif (preg_match('/^(DIRECT DEBIT|CARD PAYMENT|BILL PAYMENT|STANDING ORDER|CHARGES|CASH WITHDRAWAL|ATM|CHEQUE|BANK GIRO|OVERDRAFT|INTEREST CHARGED|FASTER PAYMENT TO)/i', $description)) {
            $hint = -1;
        }
        $key = mb_strtolower(mb_substr(trim((string) preg_replace('/[\d\/\-.,]+/', ' ', $description)), 0, 60));

        return [
            'page' => $rec['page'],
            'desc' => $description,
            'abs' => abs($money[0]),
            'balance' => count($money) === 2 ? $money[1] : null,
            'hint' => $hint,
            'key' => $key,
        ];
    }

    private function resolveDate(int $d, int $m, ?string $start, ?string $end, ?string $prevEnd, ?string $nextStart): ?string
    {
        $mk = fn (int $y) => checkdate($m, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;

        if ($start && $end) {
            $lo = strtotime($start . ' -7 days');
            $hi = strtotime($end . ' +7 days');
            foreach (array_unique([(int) substr($start, 0, 4), (int) substr($end, 0, 4)]) as $y) {
                $c = $mk($y);
                if ($c !== null && strtotime($c) >= $lo && strtotime($c) <= $hi) {
                    return $c;
                }
            }
            return $mk((int) substr($end, 0, 4));
        }
        if ($nextStart) {
            $y = (int) substr($nextStart, 0, 4);
            $c = $mk($y);
            if ($c !== null && strtotime($c) > strtotime($nextStart)) {
                $c = $mk($y - 1);
            }
            return $c;
        }
        if ($prevEnd) {
            $y = (int) substr($prevEnd, 0, 4);
            $c = $mk($y);
            if ($c !== null && strtotime($c) < strtotime($prevEnd . ' -7 days')) {
                $c = $mk($y + 1);
            }
            return $c;
        }
        return $mk((int) date('Y'));
    }

    private function fillDates(array &$rows, ?string $fallback): void
    {
        $n = count($rows);
        for ($i = 0; $i < $n; $i++) {
            if ($rows[$i]['date'] !== null) {
                continue;
            }
            $d = null;
            for ($j = $i + 1; $j < $n && $d === null; $j++) {
                $d = $rows[$j]['date'];
            }
            for ($j = $i - 1; $j >= 0 && $d === null; $j--) {
                $d = $rows[$j]['date'];
            }
            $rows[$i]['date'] = $d ?? $fallback ?? date('Y-m-d');
        }
    }

    /** @return array{0:int,1:int,2:string}|null day, month, remainder (tolerates OCR noise like "ist", "App", "31stMar"). */
    private function rowHead(string $line): ?array
    {
        $line = preg_replace('/^\S{1,6}\s+(?=(?:[0-9Iil|SsOo]{1,2})\s*(?:st|nd|rd|th)\s*[\'’`´.]?\s*[A-Za-z]{3}\b)/u', '', $line) ?? $line;
        if (!preg_match('/^([0-9Iil|SsOo]{1,2})\s*(st|nd|rd|th)\s*[\'’`´.]?\s*([A-Za-z]{3})\b(?!\s+\d{4})\s*(.*)$/u', $line, $m)) {
            return null;
        }
        $day = (int) strtr($m[1], ['I' => '1', 'i' => '1', 'l' => '1', '|' => '1', 'S' => '5', 's' => '5', 'O' => '0', 'o' => '0']);
        $mon = null;
        $w = strtolower($m[3]);
        if (isset(self::MONTHS[$w])) {
            $mon = self::MONTHS[$w];
        } else {
            foreach (self::MONTHS as $name => $num) {
                if (levenshtein($w, $name) === 1) {
                    $mon = $num;
                    break;
                }
            }
        }
        if ($mon === null || !checkdate($mon, $day, 2000)) {
            return null;
        }
        return [$day, $mon, $m[4]];
    }

    private function isNoise(string $line): bool
    {
        foreach (self::NOISE as $re) {
            if (preg_match($re, $line)) {
                return true;
            }
        }
        return false;
    }
}