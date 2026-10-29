<?php

namespace App\Services\BankParsers;

abstract class AbstractStatementParser implements StatementParserInterface
{
    public function parse(string $text, int $pageNo = 1, bool $fromOcr = false): array
    {
        return $this->parseDocument([$pageNo => $text], $fromOcr);
    }

    protected function clean(string $line): string
    {
        $line = str_replace(["\u{a0}", "\t", '€'], ' ', $line);
        $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
        return trim($line);
    }

    /** Fix typical Tesseract confusions inside amount-shaped tokens only. */
    protected function fixAmountTokens(string $line): string
    {
        return preg_replace_callback('/(?<![\w])[£E]?[\dOolI,]*\d[\dOolI,]*\.[\dOolI]{2}(?![\w])/u', function ($m) {
            $t = strtr($m[0], ['O' => '0', 'o' => '0', 'l' => '1', 'I' => '1']);
            return $t;
        }, $line) ?? $line;
    }

    protected function toFloat(string $raw): float
    {
        $neg = str_starts_with(trim($raw), '-');
        $v = (float) str_replace(['£', ',', ' ', '-'], '', $raw);
        return $neg ? -$v : $v;
    }

    /**
     * Pull up to $max trailing money tokens off a string.
     * @return array{0:string,1:array<int,float>}
     */
    protected function trailingMoney(string $text, int $max = 3): array
    {
        $tokens = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $nums = [];
        while ($tokens && count($nums) < $max
            && preg_match('/^-?£?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}$/u', (string) end($tokens))) {
            array_unshift($nums, $this->toFloat((string) array_pop($tokens)));
        }
        return [implode(' ', $tokens), $nums];
    }

    protected function result(array $rows, int $unparsed, array $samples, ?float $opening, ?float $closing,
                              ?float $totalIn = null, ?float $totalOut = null): array
    {
        return [
            'rows' => $rows,
            'unparsed' => $unparsed,
            'samples' => array_slice($samples, 0, 5),
            'opening' => $opening,
            'closing' => $closing,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
        ];
    }

    protected function makeRow(string $date, string $description, float $amount, ?float $balance,
                               int $pageNo, int $position, bool $fromOcr): array
    {
        return [
            'transaction_date' => $date,
            'description' => mb_substr($description !== '' ? $description : 'Unlabelled transaction', 0, 500),
            'reference' => null,
            'amount' => round($amount, 2),
            'balance_after' => $balance !== null ? round($balance, 2) : null,
            'source_page' => $pageNo,
            'source_position' => $position,
            'extraction_source' => $fromOcr ? 'ocr' : 'text',
        ];
    }

    /** Turn resolved internal rows into DB-shaped rows with correct per-page positions. */
    protected function finalise(array $internal, bool $fromOcr): array
    {
        $out = [];
        $pos = [];
        foreach ($internal as $r) {
            $pos[$r['page']] = ($pos[$r['page']] ?? 0) + 1;
            $out[] = $this->makeRow($r['date'], $r['desc'], $r['sign'] * $r['abs'], $r['balance'], $r['page'], $pos[$r['page']], $fromOcr);
        }
        return $out;
    }
}
