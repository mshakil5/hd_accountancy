<?php

namespace App\Services\BankParsers;

class StarlingParser extends AbstractStatementParser
{
    private const TYPES = [
        'CASH DEPOSIT CHARGE', 'CASH DEPOSIT', 'FASTER PAYMENT', 'DIRECT DEBIT', 'STANDING ORDER', 'APPLE PAY',
        'GOOGLE PAY', 'ONLINE PAYMENT', 'CARD PAYMENT', 'CONTACTLESS', 'CHIP & PIN', 'BANK TRANSFER',
        'INTEREST PAYMENT', 'CASH WITHDRAWAL', 'CASH WITHDRAWAL CHARGE', 'ATM', 'INTERNAL TRANSFER', 'TRANSFER',
        'CHEQUE', 'BACS', 'CHAPS', 'REFUND', 'FEE', 'CHARGE', 'PAYMENT', 'DIRECT CREDIT', 'BILL PAYMENT',
    ];

    /** Type → forced direction (-1 out, 1 in). */
    private const TYPE_HINT = [
        'CASH DEPOSIT CHARGE' => -1, 'CASH DEPOSIT' => 1, 'DIRECT DEBIT' => -1, 'STANDING ORDER' => -1,
        'APPLE PAY' => -1, 'GOOGLE PAY' => -1, 'ONLINE PAYMENT' => -1, 'CARD PAYMENT' => -1, 'CONTACTLESS' => -1,
        'CHIP & PIN' => -1, 'CASH WITHDRAWAL' => -1, 'CASH WITHDRAWAL CHARGE' => -1, 'ATM' => -1,
        'INTEREST PAYMENT' => 1, 'FEE' => -1, 'CHARGE' => -1, 'DIRECT CREDIT' => 1, 'REFUND' => 1,
    ];

    private const NOISE = [
        '/^24hr customer service/i', '/^www\.starlingbank\.com/i', '/^starling bank is registered/i',
        '/^e1 6pw\./i', '/^registration number/i', '/^our terms and conditions/i',
        '/^starling bank limited is a member/i', '/^compensation provided by/i', '/^list which are available/i',
        '/^date\s+type\s+transaction/i', '/^end of day/i', '/^account$/i', '/^balance$/i', '/^\d{1,2}$/',
        '/^in\s+out$/i', '/^date$/i', '/^type$/i', '/^transaction$/i',
    ];

    public function name(): string
    {
        return 'Starling';
    }

    public function canHandle(string $text, array $meta = []): bool
    {
        $h = mb_strtolower(mb_substr($text, 0, 8000));
        return (bool) (preg_match('/\bstarling\b/u', $h) || preg_match('/\bsrlg/u', $h)
            || (preg_match('/\b60-83-71\b/', $h)));
    }

    public function parseDocument(array $pages, bool $fromOcr = false): array
    {
        $raw = [];
        $unparsed = 0;
        $samples = [];
        $opening = $closing = $totalIn = $totalOut = null;
        $cur = null;
        $inTable = false;
        $done = false;
        $typeRe = '(' . implode('|', array_map(fn ($t) => preg_quote($t, '/'), self::TYPES)) . ')';

        $flush = function () use (&$cur, &$raw, &$unparsed, &$samples) {
            if ($cur !== null) {
                $this->finishRecord($cur, $raw, $unparsed, $samples);
                $cur = null;
            }
        };

        foreach ($pages as $pageNo => $text) {
            foreach (preg_split('/\R/u', (string) $text) ?: [] as $rawLine) {
                if ($done) {
                    break 2;
                }
                $line = $this->fixAmountTokens($this->fixPound($this->clean((string) $rawLine)));
                if ($line === '') {
                    continue;
                }

                // Summary block
                if (!$inTable) {
                    if (preg_match('/^Opening Balance\s+£?\s?(-?[\d,]+\.\d{2})$/iu', $line, $m)) {
                        $opening = $this->toFloat($m[1]);
                        continue;
                    }
                    if (preg_match('/^Closing Balance\s+£?\s?(-?[\d,]+\.\d{2})$/iu', $line, $m)) {
                        $closing = $this->toFloat($m[1]);
                        continue;
                    }
                    if (preg_match('/^Payments In\s+£?\s?([\d,]+\.\d{2})$/iu', $line, $m)) {
                        $totalIn = $this->toFloat($m[1]);
                        continue;
                    }
                    if (preg_match('/^Payments Out\s+£?\s?([\d,]+\.\d{2})$/iu', $line, $m)) {
                        $totalOut = $this->toFloat($m[1]);
                        continue;
                    }
                }

                if (preg_match('/^OPENING BALANCE\s*£?\s?(-?[\d,]+\.\d{2})$/iu', $line, $m)) {
                    $inTable = true;
                    $opening = $this->toFloat($m[1]);
                    continue;
                }
                if (preg_match('/^(We charge interest|Date range applicable|Interest rate (paid|charged))/iu', $line)) {
                    $flush();
                    if ($inTable) {
                        $done = true;
                    }
                    continue;
                }

                if (preg_match('#^(\d{2})/(\d{2})/(\d{4})\s+' . $typeRe . '\b\s*(.*)$#u', $line, $m)
                    && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                    $flush();
                    $inTable = true;
                    $cur = [
                        'page' => (int) $pageNo,
                        'date' => sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]),
                        'type' => $m[4],
                        'rest' => $m[5],
                    ];
                    continue;
                }

                if ($cur === null || $this->isNoise($line)) {
                    continue;
                }
                $cur['rest'] = trim($cur['rest'] . ' ' . $line);
            }
        }
        $flush();

        $rows = $raw;
        $res = BalanceResolver::resolve($rows, $opening, $closing);
        $unparsed += $res['unresolved'];
        $samples = array_merge($samples, $res['samples']);

        return $this->result($this->finalise($rows, $fromOcr), $unparsed, $samples, $opening, $closing, $totalIn, $totalOut);
    }

    private function finishRecord(array $cur, array &$raw, int &$unparsed, array &$samples): void
    {
        $tokens = preg_split('/\s+/u', trim($cur['rest']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $money = [];
        $desc = [];
        foreach ($tokens as $t) {
            if (preg_match('/^-?£\s?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}$/u', $t)) {
                $money[] = $this->toFloat($t);
            } else {
                $desc[] = $t;
            }
        }

        if (count($money) < 1 || count($money) > 2) {
            $unparsed++;
            if (count($samples) < 5) {
                $samples[] = mb_substr($cur['date'] . ' ' . $cur['type'] . ' ' . $cur['rest'], 0, 200);
            }
            return;
        }

        $description = trim(implode(' ', $desc));
        $payee = mb_strtolower(trim((string) preg_replace('/\s*\(.*$/u', '', $description)));
        $raw[] = [
            'page' => $cur['page'],
            'date' => $cur['date'],
            'desc' => trim($cur['type'] . ' ' . $description),
            'abs' => abs($money[0]),
            'balance' => count($money) === 2 ? $money[1] : null,
            'hint' => self::TYPE_HINT[$cur['type']] ?? 0,
            'key' => $cur['type'] . '|' . $payee,
        ];
    }

    /** OCR often reads "£" as "E", "f" or "2" glued to the amount. */
    private function fixPound(string $line): string
    {
        return preg_replace('/(?<=\s)[Ef](?=\d[\d,]*\.\d{2}\b)/u', '£', $line) ?? $line;
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
