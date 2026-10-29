<?php

namespace App\Services\BankParsers;

use Carbon\Carbon;

/**
 * Fallback parser – improved version of the original generic logic.
 * Used only when no bank-specific parser claims the document.
 */
class GenericParser implements StatementParserInterface
{
    private const DATE_LINE = '/^(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{1,2}\s+[A-Za-z]{3,9}\s+\d{2,4})\b\s*(.*)$/u';

    public function name(): string
    {
        return 'Generic';
    }

    public function canHandle(string $text, array $meta = []): bool
    {
        // Always available as fallback
        return true;
    }

    public function parseDocument(array $pages, bool $fromOcr = false): array
    {
        $rows = [];
        $unparsed = 0;
        $samples = [];
        foreach ($pages as $pageNo => $text) {
            $res = $this->parse((string) $text, (int) $pageNo, $fromOcr);
            $rows = array_merge($rows, $res['rows']);
            $unparsed += $res['unparsed'];
            $samples = array_merge($samples, $res['samples']);
        }
        return [
            'rows' => $rows, 'unparsed' => $unparsed, 'samples' => array_slice($samples, 0, 5),
            'opening' => null, 'closing' => null, 'total_in' => null, 'total_out' => null,
        ];
    }

    public function parse(string $text, int $pageNo = 1, bool $fromOcr = false): array
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

            // Glue "30/04/2025Joseph" → "30/04/2025 Joseph"
            if (preg_match('/^(\d{2}[\/\-]\d{2}[\/\-]\d{4})(?=[^\d\s])/u', $line, $glued)) {
                $line = $glued[1] . ' ' . mb_substr($line, mb_strlen($glued[1]));
            }

            if (preg_match(self::DATE_LINE, $line, $m) && ($date = $this->parseDate($m[1]))) {
                $tail = trim($m[2]);

                // Statement period, not a transaction
                if ($tail !== '' && preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $tail)) {
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }

                $split = $tail === '' ? null : $this->splitAmounts($tail);
                if ($split !== null) {
                    [$description, $amount, $balance] = $split;
                    $rows[] = $this->makeRow($date, $description, $amount, $balance, $pageNo, ++$position, $fromOcr);
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }

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
                    $rows[] = $this->makeRow($pendingDate, $description, $amount, $balance, $pageNo, ++$position, $fromOcr);
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }
                if (str_contains($line, '£') || $pendingLines >= 3 || mb_strlen($pendingDescription) > 240) {
                    $pendingDate = null;
                    $pendingDescription = '';
                    continue;
                }
                $pendingDescription = trim($pendingDescription . ' ' . $line);
                $pendingLines++;
                continue;
            }

            if (preg_match('/^(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{1,2}\s+[A-Za-z]{3,9}\s+\d{2,4})/', $line)
                && preg_match('/[+-]?\s*£?\s*[\d,]+\.\d{2}/', $line)) {
                $unparsed++;
                if (count($samples) < 5) {
                    $samples[] = mb_substr($line, 0, 200);
                }
            }
        }

        if ($pendingDate instanceof Carbon) {
            $unparsed++;
            if (count($samples) < 5) {
                $samples[] = mb_substr('Unfinished entry: ' . $pendingDescription, 0, 200);
            }
        }

        return ['rows' => $rows, 'unparsed' => $unparsed, 'samples' => $samples];
    }

    private function splitAmounts(string $tail): ?array
    {
        // Description + amount + optional balance
        if (!preg_match('/^(.*?)([+-]?\s*£?\s*[\d,]+\.\d{2})\s*([+-]?\s*£?\s*[\d,]+\.\d{2})?\s*$/u', $tail, $m)) {
            return null;
        }
        $description = trim($m[1]);
        $amountRaw = trim($m[2]);
        $balanceRaw = isset($m[3]) && trim($m[3]) !== '' ? trim($m[3]) : null;
        $balance = $balanceRaw !== null ? $this->toAmount($balanceRaw) : null;

        // Unsigned amount only trusted when a balance column is present
        if (!preg_match('/^[+-]/', $amountRaw) && $balance === null) {
            return null;
        }

        return [$description !== '' ? $description : 'Unlabelled transaction', $this->toAmount($amountRaw), $balance];
    }

    private function parseDate(string $raw): ?Carbon
    {
        $formats = ['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'd M Y', 'j M Y', 'd F Y', 'j F Y', 'd M y', 'j M y'];
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, trim($raw));
                if ($dt !== false) {
                    if ($dt->year < 100) {
                        $dt->year = 2000 + $dt->year;
                    }
                    return $dt->startOfDay();
                }
            } catch (\Throwable $e) {
            }
        }
        return null;
    }

    private function toAmount(string $raw): float
    {
        $raw = str_replace(['£', ',', ' '], '', $raw);
        return (float) $raw;
    }

    private function makeRow(Carbon $date, string $description, float $amount, ?float $balance, int $pageNo, int $position, bool $fromOcr): array
    {
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
