<?php

namespace App\Services\BankParsers;

/**
 * Virgin Money / Clydesdale / Yorkshire Bank (M Plus style) statements.
 *
 * Layout quirks handled here:
 *  - date printed once per day (first row only) and WITHOUT a year ("03 Apr")
 *  - description wraps over 2–3 lines, amount sits on the last line
 *  - separate Debits / Credits columns -> no sign in text (BalanceResolver proves it)
 *  - balance printed only at the end of a day / page
 *  - foreign currency rows: "Cls 24 Eur 32.04 / Rate 0.86 / Stg Amt 27.70 27.70"
 *  - "Balance brought forward from previous page" carries the running balance
 *  - several monthly statements may be concatenated in one PDF
 */
class VirginMoneyParser extends AbstractStatementParser
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    private const MON = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';

    /** Right-hand panel text that OCR may glue onto the end of a table line. */
    private const CURRENCIES = [
        'AED' => 1, 'AUD' => 1, 'BDT' => 1, 'BGN' => 1, 'BHD' => 1, 'BRL' => 1, 'CAD' => 1, 'CHF' => 1, 'CNY' => 1,
        'CZK' => 1, 'DKK' => 1, 'EGP' => 1, 'EUR' => 1, 'HKD' => 1, 'HUF' => 1, 'IDR' => 1, 'ILS' => 1, 'INR' => 1,
        'ISK' => 1, 'JPY' => 1, 'KES' => 1, 'KRW' => 1, 'KWD' => 1, 'LKR' => 1, 'MAD' => 1, 'MXN' => 1, 'MYR' => 1,
        'NOK' => 1, 'NZD' => 1, 'OMR' => 1, 'PHP' => 1, 'PKR' => 1, 'PLN' => 1, 'QAR' => 1, 'RON' => 1, 'RSD' => 1,
        'RUB' => 1, 'SAR' => 1, 'SEK' => 1, 'SGD' => 1, 'THB' => 1, 'TND' => 1, 'TRY' => 1, 'TWD' => 1, 'USD' => 1,
        'VND' => 1, 'ZAR' => 1, 'GEL' => 1, 'JOD' => 1, 'NPR' => 1, 'MUR' => 1, 'MVR' => 1, 'XOF' => 1, 'XAF' => 1,
    ];

    private const PANEL_TAIL = '/\s*(?:Page\s?\d+\s?of\s?\d+|Statement\s?(?:date|No)\b.*|Account\s?name\b.*|Sort\s?code\b.*|Account\s?number\b.*|IBAN\b.*|BIC\b.*|Opening\s?balance\b.*|Closing\s?balance\b.*|(?:Debits|Credits)\b.*|Deposits\s+(?:in\s+this|in\s+ae|ae\s+nea)\s+account\b.*|eligible\s+for\s+cover\b.*|for\s+cover\s+under\b.*|Financial\s+Services\b.*|Services\s+Compensation\b.*|Compensation\s+Scheme\b.*|Scheme\s*\(FSCS\)\b.*|\(FSCS\)\b.*|Scheme\b.*|\d{2}-\d{2}-\d{2}\b.*|GB\d{2}[A-Z0-9]+\b.*|YORKGB[A-Z0-9]+\b.*|MR\s+[A-Z .]+\b.*|(?:Cls|DD|SO|TB|MB|TL|EB|OD|WLT|FGN|CHQ|LTE|MT|PMT|ACC|GBP|REV|Purch|WDL|CCY|Unarr)\s*=\s*.*)$/iu';

    /** Table ends here (promo / footer text under the transactions). */
    private const STOP = '/^(Ready,? get set|Have a think|Just so you know|We.ve updated|Interest rate details|Clydesdale Bank|Financial Conduct|mortgages and|Not got the app|Did you know|You can go paper)/iu';

    private const NOISE = [
        '/^Statement\s?No\b/i', '/^Page\s?\d+\s?of\s?\d+$/i', '/^Statement\s?date\b/i', '/^Account\s?name\b/i',
        '/^(Sort\s?code|Account\s?number|IBAN|BIC)\b/i', '/^\d{2}-\d{2}-\d{2}$/', '/^\d{8}$/', '/^GB\d{2}[A-Z]{4}\S+$/',
        '/^[A-Z]{6}\d{2}[A-Z0-9]*$/', '/^(MR|MRS|MS|MISS|DR)\b[A-Z .]+$/',
        '/^\d{1,2}\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4}$/i',
        '/^(?:Deposits\s+(?:in\s+this|in\s+ae|ae\s+nea)\s+account|eligible\s+for\s+cover|for\s+cover\s+under|Financial\s+Services|Services\s+Compensation|Compensation\s+Scheme|Scheme\b|\(FSCS\))/iu',
        '/^for\s+cover\s+under/i',
        '/^Opening balance|^Closing balance|^Debits$|^Credits$/i', '/^(Debits|Credits)\s+[£E]/i',
        '/^[£E]\s?-?[\d,]+\.\d{2}$/u', '/^Your .* Account statement/i',
        '/^(Description|Debits|Credits|Balance)(\s+(Debits|Credits|Balance))*$/i',
        '/^(?:Cls|DD|SO|TB|MB|TL|EB|OD|WLT|FGN|CHQ|LTE|MT|PMT|ACC|GBP|REV|Purch|WDL|CCY|Unarr)\s*=\s*[A-Za-z ]+$/iu',
    ];

    public function name(): string
    {
        return 'Virgin Money / Clydesdale / Yorkshire';
    }

    public function canHandle(string $text, array $meta = []): bool
    {
        $h = mb_strtolower(mb_substr($text, 0, 8000));
        return (bool) preg_match('/virgin\s*money|clydesdale\s*bank|yorkshire\s*bank|\bm\s*plus\s*account\b/u', $h);
    }

    public function parseDocument(array $pages, bool $fromOcr = false): array
    {
        $all = implode("\n", array_map('strval', $pages));

        // ---- printed summary (right-hand panel) --------------------------------------------------
        $opening = $closing = null;
        if (preg_match('/(?:Opening\s?balance|Previous\s?statement)\s*[£E]?\s?(-?[\d,]+\.\d{2})/iu', $all, $m)) {
            $opening = $this->toFloat($m[1]);
        }
        if (preg_match_all('/Closing\s?balance\s*[£E]?\s?(-?[\d,]+\.\d{2})/iu', $all, $mm) && $mm[1]) {
            $closing = $this->toFloat((string) end($mm[1]));
        }
        $totalIn = $totalOut = null;
        if (preg_match('/Credits\b[^\d£E$]*[£E$]?\s?(-?[\d,]+\.\d{2})/iu', $all, $m)) {
            $totalIn = $this->toFloat($m[1]);
        }
        if (preg_match('/Debits\b[^\d£E$]*[£E$]?\s?(-?[\d,]+\.\d{2})/iu', $all, $m)) {
            $totalOut = $this->toFloat($m[1]);
        }

        // OCR merges the right-hand panel into the table line by line, so a label and its value
        // often are not adjacent ("Credits" / "Stg Amt 23.38 …" / "£0.00"). The panel values keep
        // their currency sign while table amounts lose it, so bind each label to the nearest
        // currency-signed amount within a few lines instead of the next plain number.
        $rawLines = [];
        foreach ($pages as $text) {
            foreach (preg_split('/\R/u', (string) $text) ?: [] as $rawLine) {
                $rawLines[] = (string) $rawLine;
            }
        }
        $summary = ['opening' => null, 'closing' => null, 'debits' => null, 'credits' => null];
        $waiting = [];
        foreach ($rawLines as $i => $rawLine) {
            $waiting = array_values(array_filter($waiting, fn (array $w): bool => $w[1] >= $i));
            $labels = [
                'opening' => '/Opening\s?balance\b/iu',
                'closing' => '/Closing\s?balance\b/iu',
            ];
            // The table header also contains "Debits Credits"; it is not a panel label.
            if (!preg_match('/Date\s+Description/iu', $rawLine)) {
                $labels['debits'] = '/\bDebits\b(?!\s*=)/iu';
                $labels['credits'] = '/\bCredits\b(?!\s*=)/iu';
            }
            foreach ($labels as $key => $re) {
                if (preg_match($re, $rawLine)) {
                    $waiting[] = [$key, $i + 3];
                }
            }
            if ($waiting && preg_match('/(?<!\w)[£$]\s*([\d,]+\.\d{2})/u', $rawLine, $sm)) {
                [$key] = array_pop($waiting);
                if ($summary[$key] === null) {
                    $summary[$key] = $this->toFloat($sm[1]);
                }
            }
        }
        $opening = $summary['opening'] ?? $opening;
        $closing = $summary['closing'] ?? $closing;
        $totalIn = $summary['credits'] ?? $totalIn;
        $totalOut = $summary['debits'] ?? $totalOut;

        $pageStmt = $this->pageStatementDates($pages);
        $fallbackYear = (int) date('Y');
        $firstStmt = $pageStmt ? reset($pageStmt) : null;
        if ($firstStmt !== null) {
            $fallbackYear = $firstStmt['y'];
        } elseif (preg_match('/\b\d{1,2}\s?(?:' . self::MON . ')[a-z]*\s+(20\d{2})\b/iu', $all, $m)) {
            $fallbackYear = (int) $m[1];
        }

        // Build list of known sidebar totals to strip when encountered in text.
        // NOTE: $opening and $closing are valid running balances (e.g. the last row's balance IS $closing),
        // so we only strip printed monthly totals ($totalIn, $totalOut) and dollar-signed artifacts from lines.
        $sidebarTotals = array_filter([$opening, $closing, $totalIn, $totalOut]);
        $isSidebarTotal = function (float $amt) use ($sidebarTotals): bool {
            foreach ($sidebarTotals as $s) {
                if (abs($amt - $s) < 0.005) return true;
            }
            return false;
        };
        $cleanSidebar = function (string $text) use ($totalIn, $totalOut, $fromOcr): string {
            // Strip currency-signed amounts. Tesseract produces "$" artifacts, and in OCR output
            // the table amounts lose their sign while the right-hand panel keeps "£", so a
            // currency-signed amount on a table line can only be sidebar noise.
            if ($fromOcr) {
                $text = preg_replace('/(?<!\w)[£$]\s*[\d,]+\.\d{2}/u', ' ', $text);
            } else {
                $text = preg_replace('/(?<!\w)\$[\d,]+\.\d{2}/u', ' ', $text);
            }
            // Strip amounts explicitly preceded by sidebar summary labels
            $text = preg_replace('/\b(?:Opening\s+balance|Closing\s+balance|Debits|Credits)\s*[£E]?\s*[\d,]+\.\d{2}/iu', ' ', $text);
            // Strip known sidebar summary monthly totals (Debits / Credits totals)
            foreach (array_filter([$totalIn, $totalOut]) as $val) {
                $f1 = preg_quote(number_format($val, 2, '.', ''), '/');
                $f2 = preg_quote(number_format($val, 2, '.', ','), '/');
                $text = preg_replace('/\b(?:' . $f1 . '|' . $f2 . ')\b/', ' ', $text);
            }
            // Strip common metadata
            $text = preg_replace('/\b\d{2}-\d{2}-\d{2}\b/', ' ', $text); // sort code
            $text = preg_replace('/\b(?:GB\d{2}[A-Z0-9]+|YORKGB[A-Z0-9]*)\b/i', ' ', $text); // IBAN/BIC
            $text = preg_replace('/\bMR\s+[A-Z .]{2,20}\b/i', ' ', $text); // Account holder
            $text = preg_replace('/\b(?:Account\s*(?:number|no|name)|Sort\s*code|IBAN|BIC|Statement\s*(?:date|No))\b[^\n]*/iu', ' ', $text);
            // Strip FSCS notices
            $text = preg_replace('/\bDeposits\s+(?:in|ae|nea|this)[^.\n]*?account\s*(?:are\s*eligible)?\.?/iu', ' ', $text);
            $text = preg_replace('/\b(?:eligible\s+for\s+cover\s+under\s+the|for\s+cover\s+under\s+the)\b/iu', ' ', $text);
            $text = preg_replace('/\b(?:Financial\s+Services\s+Compensation\s+Scheme|Financial\s+Services|Services\s+Compensation|Compensation\s+Scheme)\b/iu', ' ', $text);
            $text = preg_replace('/\b(?:Scheme\s*\(FSCS\)|\(FSCS\)|Scheme)\b/iu', ' ', $text);
            // Strip glossary definitions
            $text = preg_replace('/\b(?:Cls|DD|SO|TB|MB|TL|EB|OD|WLT|FGN|CHQ|LTE|MT|PMT|ACC|GBP|REV|Purch|WDL|CCY|Unarr)\s*[-=]\s*[^0-9\n]+/iu', ' ', $text);
            // Strip sidebar headers
            $text = preg_replace('/\b(?:Opening|Closing)\s+balance\b/iu', ' ', $text);
            $text = preg_replace('/\b(?:Debits|Credits)\b/iu', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        // ---- flatten to [page, line] so we can look ahead ----------------------------------------
        $lines = [];
        foreach ($pages as $pageNo => $text) {
            foreach (preg_split('/\R/u', (string) $text) ?: [] as $rawLine) {
                $line = $this->fixAmountTokens($this->clean((string) $rawLine));
                $line = $cleanSidebar($line);
                if ($line !== '') {
                    $lines[] = [(int) $pageNo, $line];
                }
            }
        }

        $raw = [];
        $unparsed = 0;
        $samples = [];
        $inTable = false;   // becomes true at the first table header / transaction date, stays true across pages
        $afterStop = false; // promo/footer text reached on the current page
        $curPage = null;
        $pending = '';
        $pendingFx = false;
        $year = null;
        $lastMonth = null;
        $lastDate = null; // date is printed once per day; later rows inherit it
        $openingFromStatement = null;

        /** Running balance printed at the top of a page / statement belongs to the previous row. */
        $attachBalance = function (?float $bal) use (&$raw, &$unparsed, &$samples) {
            if ($bal === null || !$raw) {
                return;
            }
            $k = count($raw) - 1;
            if ($raw[$k]['balance'] === null) {
                $raw[$k]['balance'] = $bal;
            } elseif (abs($raw[$k]['balance'] - $bal) > 0.004) {
                $unparsed++;
                if (count($samples) < 5) {
                    $samples[] = 'Carried-over balance ' . number_format($bal, 2, '.', '') . ' differs from last printed balance '
                        . number_format((float) $raw[$k]['balance'], 2, '.', '') . ' (' . $raw[$k]['date'] . ')';
                }
            }
        };

        /**
         * Year of a "dd Mon" date. The statement date printed on the SAME page decides it
         * (month after the statement month => previous year). Without it, roll forward from
         * the previous row (month goes backwards => new year).
         */
        $resolveYear = function (int $mon) use (&$year, &$lastMonth, &$curPage, $pageStmt, $firstStmt, $fallbackYear): int {
            $stmt = $pageStmt[$curPage] ?? null;
            if ($stmt !== null) {
                $y = $mon > $stmt['m'] ? $stmt['y'] - 1 : $stmt['y'];
            } elseif ($year === null) {
                $y = ($firstStmt !== null && $mon > $firstStmt['m']) ? $fallbackYear - 1 : $fallbackYear;
            } else {
                $y = ($lastMonth !== null && $mon < $lastMonth) ? $year + 1 : $year;
            }
            $year = $y;
            $lastMonth = $mon;
            return $y;
        };

        foreach ($lines as $i => [$pageNo, $line]) {
            if ($pageNo !== $curPage) {
                $curPage = $pageNo;
                $afterStop = false;
                $pending = '';
                $pendingFx = false;
            }

            // ---- "31 Mar 2023  Previous statement  31084.36" : opening marker, never a transaction ----
            if (preg_match('/^(?:([0-9OolI]{1,2})\s?(' . self::MON . ')[a-z]*\.?\s*(?:([0-9OolIZ]{2}\s?[0-9OolIZ]{2})\.?)?\s*)?Previous\s?statement\b\s*(.*)$/iu', $line, $p)) {
                $inTable = true;
                $afterStop = false;
                $pending = '';
                $pendingFx = false;
                $lastDate = null;
                if (($p[1] ?? '') !== '' && ($p[3] ?? '') !== '') {
                    $py = (int) strtr(preg_replace('/\s+/', '', $p[3]), 'OolIZ', '00112');
                    if ($py >= 2000 && $py <= 2100 && ($firstStmt === null || abs($py - $firstStmt['y']) <= 3)) {
                        $year = $py;
                        $lastMonth = self::MONTHS[strtolower($p[2])];
                    }
                }
                [, $nums] = $this->trailingMoney((string) $p[4], 1);
                $bal = $nums[0] ?? null;
                if ($openingFromStatement === null && $bal !== null && $raw === []) {
                    $openingFromStatement = $bal;
                }
                $attachBalance($bal);
                continue;
            }

            if (preg_match('/^Date\s+Description\b/iu', $line)) {
                $inTable = true;
                $afterStop = false;
                $pending = '';
                $pendingFx = false;
                continue;
            }
            if ($afterStop) {
                continue;
            }
            // Before the first table header only a date-looking line may open the table
            // (the header itself can be garbled by OCR or missing on continuation pages).
            if (!$inTable && !preg_match('/^[0-9OolI]{1,2}\s?(?:' . self::MON . ')\b/u', $line)) {
                continue;
            }
            if (preg_match(self::STOP, $line)) {
                $afterStop = true;
                $pending = '';
                $pendingFx = false;
                continue;
            }
            if (preg_match('/^\w{1,5}\s?=\s/u', $line) || $this->isNoise($line)) {
                continue;
            }

            // ---- date at the start of the line ---------------------------------------------------
            $content = $line;
            $dl = preg_replace_callback('/^([0-9OolI]{1,2})\s?(' . self::MON . ')\b/u',
                fn ($x) => strtr($x[1], 'OolI', '0011') . ' ' . $x[2], $line) ?? $line;
            if (preg_match('/^(\d{1,2})\s(' . self::MON . ')\b(?:\s+(\d{4}))?\s*(.*)$/iu', $dl, $m)) {
                $day = (int) $m[1];
                $mon = self::MONTHS[strtolower($m[2])];
                $rest = trim($m[4]);

                // A full "dd Mon yyyy" followed by anything is header text, not a transaction.
                if (($m[3] ?? '') !== '') {
                    continue;
                }

                $inTable = true;
                $y = $resolveYear($mon);
                $lastDate = checkdate($mon, $day, $y) ? sprintf('%04d-%02d-%02d', $y, $mon, $day) : null;
                $pending = '';
                $pendingFx = false;
                $content = $rest;
            }

            if ($content === '') {
                continue;
            }

            // ---- balance carried across a page break ---------------------------------------------
            if (preg_match('/^Balance\s?brought\s?forward\b\s*(.*)$/iu', $content, $bf)) {
                $pending = '';
                $pendingFx = false;
                $withoutText = preg_replace('/^from\s+previous\s+page\s*/iu', '', trim($bf[1])) ?? $bf[1];
                [, $nums] = $this->trailingMoney($withoutText, 1);
                $attachBalance($nums[0] ?? null);
                continue;
            }

            // --- FX DETECTION & EXTRACTION ---
            $fxMatch = false;
            if (preg_match('/^(?:[A-Za-z]{1,3}\s+)?(?:Cls|Card|Cash|Crd)\s*\d*[\s_]*(?<![A-Za-z])([A-Za-z]{3})(?![A-Za-z])/iu', $content, $fx)) {
                $code = strtoupper($fx[1] ?? '');
                if ($code !== '' && isset(self::CURRENCIES[$code])) {
                    $fxMatch = true;
                } else {
                    for ($k = 1; $k <= 4; $k++) {
                        if (!isset($lines[$i + $k]) || $lines[$i + $k][0] !== $pageNo) break;
                        if (preg_match('/^[0-9OolI]{1,2}\s?(?:' . self::MON . ')\b/u', $lines[$i + $k][1])) break;
                        if (preg_match('/^(?:R[ao]te\b|S[tl][gq9]\s?Am[tfl1]\b)/iu', $lines[$i + $k][1])) {
                            $fxMatch = true;
                            break;
                        }
                    }
                }
            }

            if ($fxMatch) {
                // Remove Rate tokens if merged on this line
                $testClean = preg_replace('/(?:Rate|Rat\s?are|Reie)\s*[0-9OolI]\.[0-9OolIa-z]+/iu', '', $content);
                preg_match_all('/\b(\d{1,3}(?:,\d{3})*\.\d{2}|\d+\.\d{2})\b/', $testClean, $nm);
                $foundNums = [];
                foreach ($nm[1] ?? [] as $rawN) {
                    $fn = (float) str_replace(',', '', $rawN);
                    if ($isSidebarTotal($fn)) continue;
                    $foundNums[] = $fn;
                }

                // Description: "Cls 77 Myr" or "Cls 77 Myr 149.00"
                if (preg_match('/^((?:[A-Za-z]{1,3}\s+)?(?:Cls|Card|Cash|Crd)\s*\d*[\s_]*[A-Za-z]{3}(?:[\s_]*\d+\.\d{2})?)/iu', $content, $dm)) {
                    $baseDesc = trim($dm[1]);
                } else {
                    $baseDesc = preg_replace('/[\s_]*[\d,]+\.\d+\s*$/u', '', $content) ?? $content;
                }

                // If line contains both foreign amount AND sterling amount (>= 2 numbers):
                if (count($foundNums) >= 2) {
                    $stgAmt = (float) $foundNums[1];
                    $bal = count($foundNums) >= 3 ? (float) $foundNums[2] : null;
                    if ($bal !== null && $isSidebarTotal($bal)) {
                        $bal = null;
                    }
                    if ($lastDate !== null) {
                        $raw[] = [
                            'page' => $pageNo,
                            'date' => $lastDate,
                            'desc' => $baseDesc,
                            'abs' => $stgAmt,
                            'balance' => $bal,
                            'hint' => -1,
                            'key' => $this->key($baseDesc),
                        ];
                        $pending = '';
                        $pendingFx = false;
                        continue;
                    }
                }

                // Normal FX: store description and wait for Rate / Stg Amt
                $pending = $baseDesc;
                $pendingFx = true;
                continue;
            }

            // If pending FX is open and this line contains Rate + Amount:
            if ($pendingFx && preg_match('/(?:Rate|Rat\s?are|Reie)\b/iu', $content)) {
                $cleanedRate = preg_replace('/(?:Rate|Rat\s?are|Reie)\s*[0-9OolI]\.[0-9OolIa-z]+/iu', '', $content);
                preg_match_all('/\b(\d{1,3}(?:,\d{3})*\.\d{2}|\d+\.\d{2})\b/', $cleanedRate, $rnm);
                $rateNums = [];
                foreach ($rnm[1] ?? [] as $rawN) {
                    $rn = (float) str_replace(',', '', $rawN);
                    if ($isSidebarTotal($rn)) continue;
                    $rateNums[] = $rn;
                }
                if ($rateNums && $lastDate !== null) {
                    $stgAmt = (float) $rateNums[0];
                    $bal = count($rateNums) >= 2 ? (float) $rateNums[1] : null;
                    if ($bal !== null && $isSidebarTotal($bal)) {
                        $bal = null;
                    }
                    $raw[] = [
                        'page' => $pageNo,
                        'date' => $lastDate,
                        'desc' => $pending,
                        'abs' => $stgAmt,
                        'balance' => $bal,
                        'hint' => -1,
                        'key' => $this->key($pending),
                    ];
                    $pending = '';
                    $pendingFx = false;
                    continue;
                }
                // Pure Rate line: drop it
                continue;
            }

            // Drop any Rate line
            if (preg_match('/^R[ao]te\b/iu', $content) || preg_match('/\bR[ao]te\s+[\d.O0oIlL,]+/iu', $content)) {
                continue;
            }

            // When pending FX is active, skip standalone foreign amount like "12.00"
            if ($pendingFx && preg_match('/^[\$£E]?[\d,]+\.\d+$/', $content)) {
                continue;
            }

            $stg = null;
            if (preg_match('/^S[tl][gq9]\s?Am[tfl1]\s*(?:g\s*)?(\d{1,3}(?:,\d{3})*\.\d{2}|\d+\.\d{2})\s*(.*)$/iu', $content, $s)) {
                $stg = $this->toFloat($s[1]);
                $content = trim($s[2]);
            }

            [$descPart, $nums] = $this->trailingMoney($content, 2);
            if (!$nums && $stg !== null) {
                $nums = [$stg];
            }

            // If trailingMoney picked up a leaked sidebar monthly total as a balance, discard it.
            // Opening/closing are valid running balances (the last row of a day/statement usually
            // equals closing), so they must never be treated as noise here.
            if (count($nums) === 2 && in_array($nums[1], array_filter([$totalOut, $totalIn]), true)) {
                array_pop($nums);
            }

            if (!$nums) {
                if (!$pendingFx) {
                    $pending = trim($pending . ' ' . $content);
                    if (mb_strlen($pending) > 300) {
                        $pending = '';
                    }
                }
                continue;
            }

            $desc = trim($pending . ' ' . $descPart);
            $pending = '';
            $pendingFx = false;

            if ($lastDate === null) {
                $unparsed++;
                if (count($samples) < 5) {
                    $samples[] = mb_substr('No date for row: ' . $desc . ' ' . implode(' ', $nums), 0, 200);
                }
                continue;
            }

            $desc = $desc !== '' ? $desc : 'Unlabelled transaction';
            $raw[] = [
                'page' => $pageNo,
                'date' => $lastDate,
                'desc' => $desc,
                'abs' => abs($nums[0]),
                'balance' => count($nums) === 2 ? $nums[1] : null,
                'hint' => $this->hint($desc),
                'key' => $this->key($desc),
            ];
        }

        $rows = $raw;
        $opening = $openingFromStatement ?? $opening;
        if ($closing === null && $rows) {
            for ($k = count($rows) - 1; $k >= 0; $k--) {
                if ($rows[$k]['balance'] !== null) {
                    $closing = $rows[$k]['balance'];
                    break;
                }
            }
        }

        $res = BalanceResolver::resolve($rows, $opening, $closing);
        $unparsed += $res['unresolved'];
        $samples = array_merge($samples, $res['samples']);

        // Several statements in one file: printed per-statement totals cannot be compared to the whole set.
        return $this->result($this->finalise($rows, $fromOcr), $unparsed, $samples, $opening, $closing, $totalIn, $totalOut);
    }

    /**
     * Statement date printed on each page (right-hand panel). A page only gets a date when it
     * shows exactly one statement date; pages with none inherit it unless a new statement
     * starts on them; pages mixing two statements get none (year is then rolled forward).
     *
     * @return array<int|string,array{y:int,m:int,d:int}>
     */
    private function pageStatementDates(array $pages): array
    {
        $out = [];
        $last = null;
        foreach ($pages as $no => $text) {
            $text = (string) $text;
            $found = [];
            if (preg_match_all('/Statement\s?date\s*(\d{1,2})\s*([A-Za-z]{3,9})\.?\s*(2[0O][0-9OolI]{2})/u', $text, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) {
                    $mon = self::MONTHS[strtolower(substr($m[2], 0, 3))] ?? null;
                    $y = (int) strtr($m[3], 'OolI', '0011');
                    $d = (int) $m[1];
                    if ($mon !== null && checkdate($mon, $d, $y)) {
                        $found[$y . '-' . $mon . '-' . $d] = ['y' => $y, 'm' => $mon, 'd' => $d];
                    }
                }
            }
            if (count($found) === 1) {
                $last = reset($found);
                $out[$no] = $last;
            } elseif (count($found) > 1) {
                $last = null;
            } elseif ($last !== null && !preg_match('/Previous\s?statement/iu', $text)) {
                $out[$no] = $last;
            } else {
                $last = null;
            }
        }
        return $out;
    }

    private function hint(string $desc): int
    {
        if (preg_match('/^(Gross\s+Interest|Interest\s+Paid)\b/i', $desc)
            || preg_match('/\b(Salary|Dividend)\b/i', $desc)
            || preg_match('/^Postcode\s+Lottery\s*$/i', $desc)) {
            return 1;
        }
        if (preg_match('/^(Cls|Card|Cash|Crd\s?\d*|DD|SO|Wlt\d*)\b/i', $desc)) {
            return -1;
        }
        return 0;
    }

    private function key(string $desc): string
    {
        $body = preg_replace('/^(Cls|Card|Cash|Crd)\s?\d*\s+/i', '', $desc) ?? $desc;
        $body = preg_replace('/[^A-Za-z ].*$/u', '', $body) ?? $body;
        return 'vm|' . mb_strtolower(trim($body));
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