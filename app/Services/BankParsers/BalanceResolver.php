<?php

namespace App\Services\BankParsers;

/**
 * Resolves money in / money out for statements by proving each group of rows
 * against the printed balances (exact subset-sum over cents).
 *
 * Row fields used: abs (float), balance (?float), hint (-1|0|1), key (string), sign (set here).
 */
final class BalanceResolver
{
    private const MAX_GROUP = 30;
    private const MAX_STATES = 2000;

    /** @return array{unresolved:int, samples:array<int,string>} */
    public static function resolve(array &$rows, ?float $opening, ?float $closing): array
    {
        $groups = self::groups($rows, $opening, $closing);
        $memory = [];
        $unique = [];

        foreach ($groups as $gi => $g) {
            [$signs, $isUnique] = self::solve($rows, $g['idx'], $g['delta'], []);
            $unique[$gi] = $signs !== null && $isUnique;
            if ($unique[$gi]) {
                foreach ($g['idx'] as $k => $i) {
                    $key = $rows[$i]['key'] ?? '';
                    if ($key !== '' && $rows[$i]['abs'] > 0) {
                        $memory[$key][$signs[$k]] = ($memory[$key][$signs[$k]] ?? 0) + 1;
                    }
                }
            }
        }

        $learned = [];
        foreach ($memory as $key => $counts) {
            $pos = $counts[1] ?? 0;
            $neg = $counts[-1] ?? 0;
            if ($pos !== $neg) {
                $learned[$key] = $pos > $neg ? 1 : -1;
            }
        }

        $unresolved = 0;
        $samples = [];
        foreach ($groups as $gi => $g) {
            [$signs] = self::solve($rows, $g['idx'], $g['delta'], $learned);
            if ($signs === null) {
                $unresolved++;
                if (count($samples) < 5) {
                    $first = $rows[$g['idx'][0]];
                    $samples[] = 'Cannot reconcile rows to balance on ' . ($first['date'] ?? '?')
                        . ' (' . count($g['idx']) . ' rows, expected change ' . number_format((float) $g['delta'], 2, '.', '') . ')';
                }
                foreach ($g['idx'] as $i) {
                    $h = $learned[$rows[$i]['key'] ?? ''] ?? ($rows[$i]['hint'] ?? 0);
                    $rows[$i]['sign'] = $h !== 0 ? $h : -1;
                }
                continue;
            }
            foreach ($g['idx'] as $k => $i) {
                $rows[$i]['sign'] = $signs[$k];
            }
        }

        return ['unresolved' => $unresolved, 'samples' => $samples];
    }

    private static function groups(array $rows, ?float $opening, ?float $closing): array
    {
        $groups = [];
        $prev = $opening;
        $cur = [];
        foreach ($rows as $i => $row) {
            $cur[] = $i;
            if ($row['balance'] !== null) {
                $groups[] = ['idx' => $cur, 'delta' => $prev !== null ? round($row['balance'] - $prev, 2) : null];
                $prev = $row['balance'];
                $cur = [];
            }
        }
        if ($cur) {
            $groups[] = ['idx' => $cur, 'delta' => ($prev !== null && $closing !== null) ? round($closing - $prev, 2) : null];
        }
        return $groups;
    }

    /** @return array{0:?array<int,int>,1:bool} */
    private static function solve(array $rows, array $idx, ?float $delta, array $learned): array
    {
        $n = count($idx);
        $hints = [];
        $cents = [];
        foreach ($idx as $k => $i) {
            $cents[$k] = (int) round($rows[$i]['abs'] * 100);
            $h = $learned[$rows[$i]['key'] ?? ''] ?? 0;
            $type = $rows[$i]['hint'] ?? 0;
            // A forced type hint (card payment, direct debit…) beats learned payee direction.
            $hints[$k] = $type !== 0 ? $type : $h;
        }

        if ($delta === null) {
            return [self::hintSigns($hints), false];
        }

        $target = (int) round($delta * 100);

        // Long groups (a busy day where the balance is only printed once) cannot be solved by
        // the full subset-sum table, but the printed balance still proves the right answer:
        // start from the payee hints and flip the one or two rows that close the gap.
        if ($n > self::MAX_GROUP) {
            return [self::solveLarge($cents, $hints, $target), false];
        }
        $levels = [[0 => [0, 1, null, 0]]];
        for ($k = 0; $k < $n; $k++) {
            $next = [];
            foreach ($levels[$k] as $sum => [$cost, $count]) {
                $options = $cents[$k] === 0 ? [1] : [1, -1];
                foreach ($options as $sign) {
                    $ns = $sum + $sign * $cents[$k];
                    $c = $cost + (($hints[$k] !== 0 && $hints[$k] !== $sign) ? 1 : 0);
                    if (!isset($next[$ns])) {
                        $next[$ns] = [$c, $count, $sum, $sign];
                    } else {
                        $next[$ns][1] = min(2, $next[$ns][1] + $count);
                        if ($c < $next[$ns][0]) {
                            $next[$ns][0] = $c;
                            $next[$ns][2] = $sum;
                            $next[$ns][3] = $sign;
                        }
                    }
                }
            }
            if (count($next) > self::MAX_STATES) {
                return [null, false];
            }
            $levels[] = $next;
        }

        if (!isset($levels[$n][$target])) {
            return [null, false];
        }

        $signs = [];
        $s = $target;
        for ($k = $n; $k >= 1; $k--) {
            [, , $ps, $sg] = $levels[$k][$s];
            $signs[$k - 1] = $sg;
            $s = $ps;
        }
        ksort($signs);

        return [$signs, $levels[$n][$target][1] === 1];
    }

    private static function hintSigns(array $hints): array
    {
        return array_map(fn ($h) => $h !== 0 ? $h : -1, $hints);
    }

    /**
     * Sign assignment for groups too long for the subset-sum table: take the payee hints, then
     * correct them by flipping the single row (or the pair of rows) that makes the group add up
     * to the printed balance change. Returns null when no such correction exists.
     *
     * @param  array<int,int> $cents
     * @param  array<int,int> $hints
     * @return array<int,int>|null
     */
    private static function solveLarge(array $cents, array $hints, int $target): ?array
    {
        $signs = [];
        $sum = 0;
        foreach ($cents as $k => $c) {
            $s = $hints[$k] !== 0 ? $hints[$k] : -1;
            $signs[$k] = $s;
            $sum += $s * $c;
        }

        $diff = $target - $sum;
        if ($diff === 0) {
            return $signs;
        }

        foreach ($cents as $a => $c) {
            if ($c !== 0 && $signs[$a] * $c * 2 === -$diff) {
                $signs[$a] = -$signs[$a];
                return $signs;
            }
        }

        foreach ($cents as $a => $ca) {
            if ($ca === 0) {
                continue;
            }
            $rest = $diff + 2 * $signs[$a] * $ca;
            foreach ($cents as $b => $cb) {
                if ($b <= $a || $cb === 0) {
                    continue;
                }
                if ($signs[$b] * $cb * 2 === -$rest) {
                    $signs[$a] = -$signs[$a];
                    $signs[$b] = -$signs[$b];
                    return $signs;
                }
            }
        }

        return null;
    }
}