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
    private const STOP = '/^(\[side panel\]|Ready,? get set|Have a think|Just so you know|We.ve updated|Interest rate details|Clydesdale Bank|Financial Conduct|mortgages and|Not got the app|Did you know|You can go paper)/iu';

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
        // Leftovers from the panel sentence once its boilerplate words are stripped
        // ("for cover under the Financial", "Deposits in this account are").
        '/^(?:Financial|Services|Compensation|Scheme|eligible|are|is|for|cover|under|the|co|ae|nea)$/iu',
    ];

    /**
     * Right-hand panel says "Deposits in this account are eligible for cover under the Financial
     * Services Compensation Scheme (FSCS)". OCR splits it mid-sentence, so a fragment such as
     * "Co in this account are" survives the phrase filters below.
     */
    private const PANEL_WORDS = [
        'deposits' => 1, 'in' => 1, 'this' => 1, 'account' => 1, 'are' => 1, 'eligible' => 1,
        'for' => 1, 'cover' => 1, 'under' => 1, 'the' => 1, 'financial' => 1, 'services' => 1,
        'compensation' => 1, 'scheme' => 1, 'fscs' => 1, 'co' => 1, 'ae' => 1, 'nea' => 1, 'ne' => 1,
        'eae' => 1, 'ais' => 1,
    ];

    /** OCR tail after a printed amount: a balance with the punctuation lost and/or the next row's day. */
    private const OCR_TAIL_TOKEN = '/^[\dOolI]{1,9}$/u';

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
        $cleanSidebar = function (string $text, bool $cropped = false) use ($totalIn, $totalOut, $fromOcr): string {
            // Strip currency-signed amounts. Tesseract produces "$" artifacts, and in OCR output
            // the table amounts lose their sign while the right-hand panel keeps "£", so a
            // currency-signed amount on a table line can only be sidebar noise.
            if ($cropped) {
                // Layout OCR already split the panel off, so a glyph in front of an amount is a
                // misread digit ("$1.23" for 51.23): keep the amount, balance repair fixes the digit.
                $text = preg_replace('/(?<![\w])[$§£]\s?(?=[\d,]*\.\d{2}\b)/u', '0', $text);
            } elseif ($fromOcr) {
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
            $text = preg_replace('/\bDeposits\s+(?:in|ae|nea|eae|ais|this)[^.\n]*?account\s*(?:(?:are|is)\s*)?(?:eligible)?\.?/iu', ' ', $text);
            $text = preg_replace('/\b(?:eligible\s+for\s+cover\s+under\s+the|for\s+cover\s+under\s+the)\b/iu', ' ', $text);
            $text = preg_replace('/\b(?:Financial\s+Services\s+Compensation\s+Scheme|Financial\s+Services|Services\s+Compensation|Compensation\s+Scheme)\b/iu', ' ', $text);
            // \b does not exist before "(", so the old \b\(FSCS\)\b never matched.
            $text = preg_replace('/(?:Scheme\s*\(FSCS\)|\(FSCS\)|\bScheme\b)/iu', ' ', $text);
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
            $cropped = str_contains((string) $text, '[side panel]');
            foreach (preg_split('/\R/u', (string) $text) ?: [] as $rawLine) {
                $line = $this->fixAmountTokens($this->clean((string) $rawLine));
                $line = $this->normalizeDecimalCommas($line);
                $line = $cleanSidebar($line, $cropped);
                $line = $this->stripMarginJunk($line);
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
        // Set when a row's amount was recovered from an OCR-garbled line: the rest of that
        // row's description follows on the next lines, so it must not spill into the next row.
        $orphanRow = null;
        $orphanLines = 0;

        /** Running balance printed at the top of a page / statement belongs to the previous row. */
        $attachBalance = function (?float $bal) use (&$raw, &$unparsed, &$samples) {
            if ($bal === null || !$raw) {
                return;
            }
            $k = count($raw) - 1;
            if ($raw[$k]['balance'] === null) {
                $raw[$k]['balance'] = $bal;
            } elseif (abs($raw[$k]['balance'] - $bal) > 0.004) {
                // The same balance is printed twice (end of page / top of next page). When the two
                // readings differ one of them is an OCR misread: remember both, repairByBalances()
                // keeps whichever the running balance proves.
                $raw[$k]['balAlt'] = $bal;
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
                $orphanRow = null;
                $orphanLines = 0;
            }

            // ---- "31 Mar 2023  Previous statement  31084.36" : opening marker, never a transaction ----
            $isPrev = preg_match('/^(?:([0-9OolI]{1,2})\s?(' . self::MON . ')[a-z]*\.?\s*(?:([0-9OolIZ]{2}\s?[0-9OolIZ]{2})\.?)?\s*)?[^A-Za-z0-9]*Previous\s?statement\b\s*(.*)$/iu', $line, $p);
            if (!$isPrev && !preg_match('/\b(?:C[il1]s|Card|Cash|DD|Mb)\b|forward|brought|previou\w*\s+pa/iu', $line)
                && preg_match('/\bPrevio\w{0,3}(?:\s?st\w*)?\s*(.*)$/iu', $line, $pg)) {
                // "SAS Eas ... O01May2025 = Previou 12866.97": label garbled by OCR, still a statement opener.
                $p = [$line, '', '', '', (string) $pg[1]];
                $isPrev = true;
            }
            if ($isPrev) {
                $inTable = true;
                $afterStop = false;
                $pending = '';
                $pendingFx = false;
                $lastDate = null;
                $orphanRow = null;
                $orphanLines = 0;
                if (($p[1] ?? '') !== '' && ($p[3] ?? '') !== '') {
                    $py = (int) strtr(preg_replace('/\s+/', '', $p[3]), 'OolIQZ', '001102');
                    if ($py >= 2000 && $py <= 2100 && ($firstStmt === null || abs($py - $firstStmt['y']) <= 3)) {
                        $year = $py;
                        $lastMonth = self::MONTHS[strtolower($p[2])];
                    }
                }
                [, $nums] = $this->trailingMoney((string) $p[4], 1);
                $bal = $nums[0] ?? null;
                if ($bal === null && preg_match('/(?<![\d.,])(\d{4,9})\s*$/u', (string) $p[4], $dm)) {
                    $bal = round(((int) $dm[1]) / 100, 2); // "1523713" -> 15237.13
                }
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
                $orphanRow = null;
                $orphanLines = 0;
                continue;
            }
            if ($afterStop) {
                continue;
            }
            // Before the first table header only a date-looking line may open the table
            // (the header itself can be garbled by OCR or missing on continuation pages).
            if (!$inTable && !preg_match('/^[0-9OolIQ]{1,3}\s*(?:' . self::MON . ')\b/u', $line)) {
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
            $dl = preg_replace_callback('/^([0-9OolIQ]{1,3})\s*(' . self::MON . ')\b/u', function ($x) {
                // OCR sometimes prefixes the day ("O05 Apr", "l0 Apr"). Keep the last
                // two digits so the normal date pattern below can still read the line.
                $digits = preg_replace('/\D/', '', strtr($x[1], 'OolIQ', '00110')) ?? '';
                $day = (int) substr($digits, -2);
                return ($day >= 1 && $day <= 31 ? sprintf('%02d', $day) : $x[1]) . ' ' . $x[2];
            }, $line) ?? $line;
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
                $orphanRow = null;
                $orphanLines = 0;
                $content = $rest;
            }

            if ($content === '') {
                continue;
            }

            // ---- balance carried across a page break ---------------------------------------------
            if (preg_match('/^Balance\s?brought\s?forward\b|\bforward\s+from\s+previo|\bbrought\b.{0,30}\bforward\b/iu', $content)) {
                $pending = '';
                $pendingFx = false;
                $orphanRow = null;
                $orphanLines = 0;
                // The label can be garbled by OCR; the printed balance is whatever amount ends the line.
                $bfBal = null;
                if (preg_match('/(-?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2})\s*$/u', $content, $bm)) {
                    $bfBal = $this->toFloat($bm[1]);
                } elseif (preg_match('/(?:page|previo\w*)\s+(\d{4,9})\s*$/iu', $content, $bm)) {
                    $bfBal = round(((int) $bm[1]) / 100, 2);
                }
                // A file that starts part-way through a statement (no "Previous statement" row)
                // begins at the carried-over balance, not at the statement's printed opening balance.
                if ($bfBal !== null && $raw === [] && $openingFromStatement === null) {
                    $openingFromStatement = $bfBal;
                }
                $attachBalance($bfBal);
                continue;
            }

            // --- FX DETECTION & EXTRACTION ---
            $fxMatch = false;
            if (preg_match('/^(?:[0-9OolIQ]{1,4}\s*(?:' . self::MON . ')\s+)?(?:[A-Za-z]{1,3}\s+|[^\w\s]{1,4}\s+)?(?:C[il1]s?|Card|Cash|Crd)\s*\d*[\s_]*(?<![A-Za-z])([A-Za-z]{3})(?![A-Za-z])/iu', $content, $fx)) {
                $code = strtoupper($fx[1] ?? '');
                if ($code !== '' && isset(self::CURRENCIES[$code])) {
                    $fxMatch = true;
                } else {
                    for ($k = 1; $k <= 4; $k++) {
                        if (!isset($lines[$i + $k]) || $lines[$i + $k][0] !== $pageNo) break;
                        if (preg_match('/^[0-9OolIQ]{1,3}\s*(?:' . self::MON . ')\b/u', $lines[$i + $k][1])) break;
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
                if (preg_match('/^(?:[0-9OolIQ]{1,4}\s*(?:' . self::MON . ')\s+)?((?:[A-Za-z]{1,3}\s+|[^\w\s]{1,4}\s+)?(?:C[il1]s?|Card|Cash|Crd)\s*\d*[\s_]*[A-Za-z]{3}(?:[\s_]*\d+\.\d{2})?)/iu', $content, $dm)) {
                    $baseDesc = trim(preg_replace('/^[^\w£]+/u', '', trim($dm[1])) ?? $dm[1]);
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
            $stgDesc = '';
            if (preg_match('/(?:^|\s)S[tl][gq9]\s?Am[tfl1]\s*(?:g\s*)?(\d{1,3}(?:,\d{3})*\.\d{2}|\d+\.\d{2})\s*(.*)$/iu', $content, $s, PREG_OFFSET_CAPTURE)) {
                $stg = $this->toFloat($s[1][0]);
                // OCR sometimes glues debris in front of the label ("3 Stg Amt 2.21 2.21").
                // Keep a real description, drop a bare digit/punctuation prefix.
                $before = trim(substr($content, 0, (int) $s[0][1]));
                if ($before !== '' && preg_match('/[A-Za-z]{2,}/u', $before)) {
                    $stgDesc = $before;
                }
                $content = trim($s[2][0]);
            }

            $salvaged = false;
            if ($stg !== null) {
                // "Stg Amt 23.38 | Debits 23.38 | Balance 10657.21" is OCR'd as
                // "Stg Amt 23.38 23.38 1065721": the second amount repeats the debit
                // column and the last value is the running balance with its decimal
                // point lost. They must become amount + balance, not description text.
                [$descPart, $fxBalance] = $this->fxTail($content, $stg);
                if ($fxBalance !== null && !$this->plausibleBalance($fxBalance, $raw, $openingFromStatement ?? $opening)) {
                    // A badly misread digit run ("2695282" for a ~10,254.82 balance) must
                    // not become a fake PDF balance - that would only create a mismatch.
                    $fxBalance = null;
                }
                $descPart = trim($stgDesc . ' ' . $descPart);
                $nums = $fxBalance !== null ? [$stg, $fxBalance] : [$stg];
            } else {
                [$descPart, $nums] = $this->trailingMoney($content, 2);
                if (!$nums) {
                    // OCR often loses the comma/decimal point of the printed running balance
                    // ("14,625.51" -> "1462551") and glues the next row's day onto the line:
                    // "Mb Gillian Vickerm 453.00 1462551 04". The amount is still printed there,
                    // so recover it instead of treating the whole line as description text -
                    // otherwise the transaction only surfaces later, against the next row's
                    // amount ("12.25" where the statement says 453.00).
                    $salvage = $this->salvageAmounts($content);
                    if ($salvage !== null) {
                        [$descPart, $nums] = $salvage;
                        if (count($nums) === 2 && !$this->plausibleBalance($nums[1], $raw, $openingFromStatement ?? $opening)) {
                            // A garbled tail must not become a fake printed balance - that would only create a mismatch.
                            array_pop($nums);
                        }
                        $salvaged = true;
                    } else {
                        // OCR also glues right-hand panel text AFTER the amount
                        // ("Aspley 4.75 Financial", "Aspley 8.80 (FSCS)"). The amount is
                        // printed, but no longer the last token, so trailingMoney() missed
                        // it and the row would be swallowed into the next description.
                        [$descPart, $nums] = $this->moneyBeforeJunk($content, 2);
                        if (!$nums && preg_match('/^(.*?\S)\s+(\d{1,3}(?:,\d{3})*\.\d{2})\s+(?:\S*\d\S*\s*){1,2}$/u', $content, $gm)) {
                            // amount read, running balance too garbled to use: keep the amount, repair finds the rest
                            [$descPart, $nums] = [$gm[1], [$this->toFloat($gm[2])]];
                        }
                        if (count($nums) === 2 && !$this->plausibleBalance($nums[1], $raw, $openingFromStatement ?? $opening)) {
                            array_pop($nums);
                        }
                    }
                }
            }

            // If trailingMoney picked up a leaked sidebar monthly total as a balance, discard it.
            // Opening/closing are valid running balances (the last row of a day/statement usually
            // equals closing), so they must never be treated as noise here.
            if (count($nums) === 2 && in_array($nums[1], array_filter([$totalOut, $totalIn]), true)) {
                array_pop($nums);
            }

            if (!$nums) {
                if (!$pendingFx) {
                    // A loosely-OCR'd date ("Q7 Jan …") starts a new day: never let it join the
                    // previous row even when the day itself could not be decoded.
                    $newDay = (bool) preg_match('/^[0-9OolIQ]{1,3}\s*(?:' . self::MON . ')\b/iu', $content);
                    if ($orphanRow !== null && !$newDay && $orphanLines < 3) {
                        // The amount line was cut short by OCR noise, so these lines are the
                        // rest of THAT row's description - not the next transaction's.
                        $raw[$orphanRow]['desc'] = trim($raw[$orphanRow]['desc'] . ' ' . $content);
                        $raw[$orphanRow]['key'] = $this->key($raw[$orphanRow]['desc']);
                        $orphanLines++;
                    } else {
                        $orphanRow = null;
                        $pending = trim($pending . ' ' . $content);
                        if (mb_strlen($pending) > 300) {
                            $pending = '';
                        }
                    }
                }
                continue;
            }

            // A running balance OCR'd onto a description line ("Cls 77 Co-Op Group 1 15148.34"): an
            // amount within a couple of digits of the balance just printed is that balance, not a payment.
            $lastBal = null;
            for ($b = count($raw) - 1; $b >= 0; $b--) {
                if ($raw[$b]['balance'] !== null) {
                    $lastBal = (float) $raw[$b]['balance'];
                    break;
                }
            }
            $lastBal ??= $openingFromStatement ?? $opening;
            if ($lastBal !== null && $nums && $nums[0] >= 1000 && count($nums) === 1
                && abs($nums[0] - $lastBal) / max($lastBal, 1.0) < 0.25
                && levenshtein((string) (int) round($nums[0] * 100), (string) (int) round($lastBal * 100)) <= 2) {
                $pending = trim($pending . ' ' . $descPart);
                continue;
            }

            $desc = trim($pending . ' ' . $descPart);
            $pending = '';
            $pendingFx = false;

            // A lone printed amount with no description is a summary-panel value
            // ("Opening balance / 15078.51", "Debits / 1454.36"), never a transaction.
            if ($desc === '') {
                continue;
            }

            if ($lastDate === null) {
                $unparsed++;
                if (count($samples) < 5) {
                    $samples[] = mb_substr('No date for row: ' . $desc . ' ' . implode(' ', $nums), 0, 200);
                }
                continue;
            }

            $desc = $desc !== '' ? $desc : 'Unlabelled transaction';
            $segments = $this->splitPayees($desc);
            if (count($segments) > 1) {
                // Earlier payees lost their amount to OCR: keep them as "maybe" rows; the printed
                // balances later either fill the amount in or prove the row was never there.
                foreach (array_slice($segments, 0, -1) as $seg) {
                    // "Huddersfield 185" is 1.85 with the decimal point lost: use it as the reading
                    // (the balance repair corrects it if a digit is wrong too).
                    $guess = null;
                    if (preg_match('/\s(\d{3,5})$/', $seg, $gm)) {
                        $guess = ((int) $gm[1]) / 100;
                        $seg = trim(substr($seg, 0, -strlen($gm[1])));
                    }
                    $raw[] = [
                        'page' => $pageNo, 'date' => $lastDate, 'desc' => $seg, 'abs' => $guess, 'maybe' => $guess === null,
                        'suspect' => $guess !== null, 'balance' => null, 'hint' => $this->hint($seg), 'key' => $this->key($seg),
                    ];
                }
                $desc = (string) end($segments);
            }
            $raw[] = [
                'page' => $pageNo,
                'date' => $lastDate,
                'desc' => $desc,
                'abs' => abs($nums[0]),
                'balance' => count($nums) === 2 ? $nums[1] : null,
                'hint' => $this->hint($desc),
                'key' => $this->key($desc),
                // "$1.23" for 51.23 reaches here as "01.23": a leading zero marks a misread digit.
                'suspect' => (bool) preg_match('/(?<![\d.])0\d+\.\d{2}(?!\d)/', $line),
            ];
            $orphanRow = $salvaged ? count($raw) - 1 : null;
            $orphanLines = 0;
        }

        $this->repairByBalances($raw, $openingFromStatement ?? $opening, $unparsed, $samples);

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
     * Margin noise in front of a line ("oO ! 05 Oct Cash 24 Barcyork", "‘a! Cls 24 The Lockwood"):
     * one or two short junk tokens before a date or a payee marker. A line that already starts
     * with a date, or whose leading tokens are real markers (Cls, Cash, DD, Mb ...), is left alone.
     */
    private function stripMarginJunk(string $line): string
    {
        if (preg_match('/^[0-9OolIQ]{1,3}\s*(?:' . self::MON . ')\b/u', $line)) {
            return $line; // already starts with a date
        }
        if (!preg_match('/^((?:\S{1,3}\s+){1,2})(?=(?:[0-9OolIQ]{1,3}\s*(?:' . self::MON . ')\b|(?:C[il1]s|Cash|Card|Crd|DD|OD|Mb|TB)\b))/u', $line, $m)) {
            return $line;
        }
        foreach (preg_split('/\s+/', trim($m[1])) ?: [] as $t) {
            if (preg_match('/^(?:C[il1]s|Cash|Card|Crd|DD|OD|SO|Mb|TB|WLT)$/i', $t)) {
                return $line; // a real marker, not margin noise
            }
        }
        return substr($line, strlen($m[1]));
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

    /**
     * Split a description that holds several payees ("Cls 77 Thorton Fenci Huddersfield Mb Aspley
     * Services"): each payee starts with a card / cash / direct-debit / mobile-banking marker.
     *
     * @return array<int,string>
     */
    private function splitPayees(string $desc): array
    {
        $re = '/(?<![\w])(?:(?:C[il1]s|Card|Cash|Crd|W[lI1]t\d*)\s?77\b|(?:DD|OD)\s+(?=[A-Z])|Mb\s+(?=[A-Z]))/u';
        if (!preg_match_all($re, $desc, $m, PREG_OFFSET_CAPTURE) || count($m[0]) < 2) {
            return [$desc];
        }
        $starts = array_map(fn ($x) => (int) $x[1], $m[0]);
        $parts = [];
        foreach ($starts as $n => $at) {
            $to = $starts[$n + 1] ?? strlen($desc);
            $parts[] = trim(substr($desc, $at, $to - $at));
        }
        $lead = trim(substr($desc, 0, $starts[0]));
        if ($lead !== '') {
            $parts[0] = trim($lead . ' ' . $parts[0]);
        }
        return array_values(array_filter($parts, fn ($x) => $x !== ''));
    }

    /** Do the rows of this group add up to the printed balance change for some money in / out split? */
    private function fits(array $raw, array $idx, float $delta): bool
    {
        $idx = array_values(array_filter($idx, fn ($k) => $raw[$k]['abs'] !== null));
        if (!$idx) {
            return abs($delta) < 0.005;
        }
        if (count($idx) > 30) {
            return true; // too long to prove either way - leave it to the resolver
        }
        return BalanceResolver::trySolve($raw, $idx, $delta) !== null;
    }

    /** True when two cent amounts differ by at most one OCR-style edit (one wrong, lost or extra digit). */
    private function similarCents(int $a, int $b): bool
    {
        return levenshtein((string) $a, (string) $b) <= 1;
    }

    /**
     * How believable is it that OCR turned $to into $from? 0 = a typical Tesseract slip (a thin "1" lost
     * or added, or look-alike digits 5/6, 5/8, 3/8, 6/8, 0/8, 0/6, 1/7); 1 = any other single edit.
     */
    private function ocrSlip(int $from, int $to): int
    {
        $a = (string) $from;
        $b = (string) $to;
        if (strlen($a) === strlen($b)) {
            for ($i = 0; $i < strlen($a); $i++) {
                if ($a[$i] !== $b[$i]) {
                    $pair = $a[$i] < $b[$i] ? $a[$i] . $b[$i] : $b[$i] . $a[$i];
                    return in_array($pair, ['56', '58', '38', '68', '08', '06', '17', '35', '09'], true) ? 0 : 1;
                }
            }
            return 0;
        }
        $long = strlen($a) > strlen($b) ? $a : $b;
        $short = strlen($a) > strlen($b) ? $b : $a;
        for ($i = 0; $i < strlen($long); $i++) {
            if (substr($long, 0, $i) . substr($long, $i + 1) === $short) {
                return $long[$i] === '1' ? 0 : 1;
            }
        }
        return 1;
    }

    /**
     * OCR misreads digits (15118.43 -> 16118.43, 7.50 -> 7.90), drops a decimal point or loses a
     * whole amount. Every day ends with a printed running balance, so the numbers prove themselves:
     * a group whose rows do not add up to the balance change is repaired when exactly ONE
     * explanation fits (a misread balance, a misread/lost amount, or one missing row).
     *
     * @param array<int,array<string,mixed>> $raw
     * @param array<int,string> $samples
     */
    private function repairByBalances(array &$raw, ?float $opening, int &$unparsed, array &$samples): void
    {
        if ($opening !== null && $raw) {
            $prev = $opening;
            $prevBad = false; // previous group could not be proven, so its end balance may be a misread
            $i = 0;
            $guard = 0;
            while ($i < count($raw) && $guard++ < 4000) {
                $end = $i;
                while ($end < count($raw) && $raw[$end]['balance'] === null) {
                    $end++;
                }
                if ($end >= count($raw)) {
                    break; // trailing rows after the last printed balance
                }
                $idx = range($i, $end);
                $delta = round((float) $raw[$end]['balance'] - $prev, 2);

                if ($this->fits($raw, $idx, $delta)) {
                    $prev = (float) $raw[$end]['balance'];
                    $prevBad = false;
                    $i = $end + 1;
                    continue;
                }
                // The balance before this group was never proven (e.g. a lost leading "1": 7186.18 for
                // 17186.18). This group's own end balance says what it must have been.
                if ($prevBad && $i > 0) {
                    $implied = $this->impliedPrevious($raw, $idx, (float) $raw[$end]['balance']);
                    if ($implied !== null
                        && levenshtein((string) (int) round($implied * 100), (string) (int) round($prev * 100)) <= 2) {
                        $raw[$i - 1]['balance'] = $implied;
                        $prev = $implied;
                        $prevBad = false;
                        continue;
                    }
                }
                // The misread may be in the other copy of this balance (page-top "brought forward").
                if (isset($raw[$end]['balAlt']) && $this->fits($raw, $idx, round((float) $raw[$end]['balAlt'] - $prev, 2))) {
                    $raw[$end]['balance'] = (float) $raw[$end]['balAlt'];
                    unset($raw[$end]['balAlt']);
                    continue;
                }

                // A thin leading "1" is the classic Tesseract loss: 7348.27 printed as 17348.27.
                $endBal = (float) $raw[$end]['balance'];
                if ($endBal >= 1000 && $endBal < 10000 && $this->fits($raw, $idx, round($endBal + 10000 - $prev, 2))) {
                    $raw[$end]['balance'] = round($endBal + 10000, 2);
                    continue;
                }

                if ($this->resolveMaybeRows($raw, $i, $end, $delta)) {
                    $raw = array_values($raw);
                    continue;
                }

                // Where does the next group stand? A misread BALANCE breaks this group and the next one
                // in opposite directions; a misread AMOUNT breaks only this group.
                $nextEnd = $end + 1;
                while ($nextEnd < count($raw) && $raw[$nextEnd]['balance'] === null) {
                    $nextEnd++;
                }
                $nextFits = $nextEnd >= count($raw)
                    || $this->fits($raw, range($end + 1, $nextEnd), round((float) $raw[$nextEnd]['balance'] - (float) $raw[$end]['balance'], 2));

                $done = false;
                if (!$nextFits) {
                    $done = $this->mergeFix($raw, $i, $prev);
                }
                if (!$done) {
                    $done = $this->amountFix($raw, $i, $end, $delta, true)
                        || $this->amountFix($raw, $i, $end, $delta, false);
                }
                if (!$done && $nextFits) {
                    $done = $this->mergeFix($raw, $i, $prev);
                }
                if (!$done) {
                    $done = $this->placeholderFix($raw, $i, $end, $delta, $unparsed, $samples);
                    if ($done) {
                        $raw = array_values($raw);
                    }
                }
                if (!$done) {
                    // Cannot be proven: keep what was read, the resolver reports the group.
                    $prev = (float) $raw[$end]['balance'];
                    $prevBad = true;
                    $i = $end + 1;
                }
            }
        }

        // "Maybe" rows that nothing proved (and nothing disproved) have no amount: never ship them silently.
        $kept = [];
        foreach ($raw as $r) {
            if ($r['abs'] === null) {
                $unparsed++;
                if (count($samples) < 5) {
                    $samples[] = mb_substr('Amount not readable for: ' . $r['desc'] . ' (' . $r['date'] . ')', 0, 200);
                }
                continue;
            }
            unset($r['maybe'], $r['balAlt'], $r['suspect']);
            $kept[] = $r;
        }
        $raw = $kept;
    }

    /**
     * A group holding "maybe" rows (payees whose amount OCR lost): if the group already adds up
     * without them they were noise; if exactly one of them can carry the missing amount, fill it in.
     */
    private function resolveMaybeRows(array &$raw, int $i, int $end, float $delta): bool
    {
        $maybe = [];
        for ($k = $i; $k <= $end; $k++) {
            if ($raw[$k]['abs'] === null) {
                $maybe[] = $k;
            }
        }
        if (!$maybe) {
            return false;
        }
        $idx = range($i, $end);
        // 1) noise only (e.g. a wrapped description that repeats the payee)
        if ($this->fits($raw, $idx, $delta)) {
            foreach ($maybe as $k) {
                unset($raw[$k]);
            }
            return true;
        }
        // 2) one of them carries the missing amount, the others are noise
        $hit = null;
        foreach ($maybe as $k) {
            $value = $this->impliedAmount($raw, $idx, $k, $delta);
            if ($value === null) {
                continue;
            }
            $trial = $raw;
            $trial[$k]['abs'] = $value;
            if ($this->fits($trial, $idx, $delta)) {
                if ($hit !== null) {
                    return false; // ambiguous
                }
                $hit = [$k, $value];
            }
        }
        if ($hit === null) {
            return false;
        }
        $raw[$hit[0]]['abs'] = $hit[1];
        foreach ($maybe as $k) {
            if ($k !== $hit[0]) {
                unset($raw[$k]);
            }
        }
        return true;
    }

    /**
     * Amount row $k must have so that the group adds up to $delta. Rows whose direction is not
     * known are tried as money out first (transfers and card spend are almost always debits).
     */
    private function impliedAmount(array $raw, array $idx, int $k, float $delta): ?float
    {
        $fixed = 0;
        $free = [];
        foreach ($idx as $o) {
            if ($o === $k || $raw[$o]['abs'] === null) {
                continue;
            }
            $c = (int) round($raw[$o]['abs'] * 100);
            $h = (int) $raw[$o]['hint'];
            if ($h !== 0) {
                $fixed += $h * $c;
            } else {
                $free[] = $c;
            }
        }
        if (count($free) > 10) {
            return null;
        }
        $target = (int) round($delta * 100);
        $h = (int) $raw[$k]['hint'];
        $best = null;
        for ($mask = 0, $n = 1 << count($free); $mask < $n; $mask++) {
            $sum = $fixed;
            $credits = 0;
            foreach ($free as $b => $c) {
                if (($mask >> $b) & 1) {
                    $sum += $c;
                    $credits++;
                } else {
                    $sum -= $c;
                }
            }
            $x = $h !== 0 ? ($target - $sum) * $h : -($target - $sum);
            if ($x > 0 && ($best === null || $credits < $best[0])) {
                $best = [$credits, $x];
            }
        }
        return $best === null ? null : $best[1] / 100;
    }

    /** Balance that must have been printed before this group, given its end balance (rows read as money out first). */
    private function impliedPrevious(array $raw, array $idx, float $endBalance): ?float
    {
        $fixed = 0;
        $free = [];
        foreach ($idx as $k) {
            if ($raw[$k]['abs'] === null) {
                continue;
            }
            $c = (int) round($raw[$k]['abs'] * 100);
            $h = (int) $raw[$k]['hint'];
            if ($h !== 0) {
                $fixed += $h * $c;
            } else {
                $free[] = $c;
            }
        }
        if (count($free) > 10) {
            return null;
        }
        $best = null;
        for ($mask = 0, $n = 1 << count($free); $mask < $n; $mask++) {
            $sum = $fixed;
            $credits = 0;
            foreach ($free as $b => $c) {
                if (($mask >> $b) & 1) {
                    $sum += $c;
                    $credits++;
                } else {
                    $sum -= $c;
                }
            }
            if ($best === null || $credits < $best[0]) {
                $best = [$credits, (int) round($endBalance * 100) - $sum];
            }
        }
        return $best !== null && $best[1] > 0 ? $best[1] / 100 : null;
    }

    /**
     * Balance-column misread: this group and the next one fail in opposite directions, but the two
     * together add up to the later printed balance. The in-between balance is recomputed.
     */
    private function mergeFix(array &$raw, int $i, float $prev): bool
    {
        $end = $i;
        for ($hop = 0; $hop < 3; $hop++) {
            while ($end < count($raw) && $raw[$end]['balance'] === null) {
                $end++;
            }
            if ($end >= count($raw)) {
                return false;
            }
            $anchors = [];
            $next = $end + 1;
            while ($next < count($raw) && $raw[$next]['balance'] === null) {
                $next++;
            }
            if ($next >= count($raw)) {
                return false;
            }
            $end = $next; // merge with the following group
            $idx = range($i, $end);
            $delta = round((float) $raw[$end]['balance'] - $prev, 2);
            $signs = $this->fits($raw, $idx, $delta) ? BalanceResolver::trySolve($raw, array_values(array_filter($idx, fn ($k) => $raw[$k]['abs'] !== null)), $delta) : null;
            if ($signs === null) {
                continue;
            }
            // Recompute the in-between balances and make sure each is within one or two OCR edits of what was printed.
            $running = $prev;
            $new = [];
            $pos = 0;
            $valid = true;
            foreach ($idx as $k) {
                if ($raw[$k]['abs'] === null) {
                    continue;
                }
                $running = round($running + $signs[$pos++] * $raw[$k]['abs'], 2);
                if ($raw[$k]['balance'] !== null && $k !== $end) {
                    if (levenshtein((string) (int) round($raw[$k]['balance'] * 100), (string) (int) round($running * 100)) > 2) {
                        $valid = false;
                        break;
                    }
                    $new[$k] = $running;
                }
            }
            if (!$valid) {
                continue;
            }
            foreach ($new as $k => $bal) {
                $raw[$k]['balance'] = $bal;
            }
            return true;
        }
        return false;
    }

    /**
     * One amount in the group is misread (or lost): find the single value that makes the group add up
     * and is within one OCR edit of what was read.
     */
    private function amountFix(array &$raw, int $i, int $end, float $delta, bool $useHints): bool
    {
        $idx = range($i, $end);
        $target = (int) round($delta * 100);
        $found = [];
        foreach ($idx as $k) {
            if ($raw[$k]['abs'] === null) {
                continue;
            }
            $fixed = 0;
            $free = [];
            foreach ($idx as $o) {
                if ($o === $k || $raw[$o]['abs'] === null) {
                    continue;
                }
                $c = (int) round($raw[$o]['abs'] * 100);
                $h = $useHints ? (int) $raw[$o]['hint'] : 0;
                if ($h !== 0) {
                    $fixed += $h * $c;
                } else {
                    $free[] = $c;
                }
            }
            if (count($free) > 10) {
                continue;
            }
            $orig = (int) round($raw[$k]['abs'] * 100);
            $own = $useHints && (int) $raw[$k]['hint'] !== 0 ? [(int) $raw[$k]['hint']] : [1, -1];
            for ($mask = 0, $n = 1 << count($free); $mask < $n; $mask++) {
                $sum = $fixed;
                foreach ($free as $b => $c) {
                    $sum += (($mask >> $b) & 1) ? -$c : $c;
                }
                $credits = 0;
                foreach ($free as $b => $c) {
                    $credits += (($mask >> $b) & 1);
                }
                foreach ($own as $sk) {
                    $x = ($target - $sum) * $sk;
                    if ($x > 0 && $x !== $orig && $this->similarCents($orig, $x)) {
                        // Best explanation first: a flagged (garbled) amount, then the fewest credits.
                        $rank = [empty($raw[$k]['suspect']) ? 1 : 0, $credits + ($sk === 1 && (int) $raw[$k]['hint'] !== 1 ? 1 : 0), $this->ocrSlip($orig, $x)];
                        $key = $k . ':' . $x;
                        if (!isset($found[$key]) || $rank < $found[$key][2]) {
                            $found[$key] = [$k, $x, $rank];
                        }
                    }
                }
            }
        }
        if (!$found) {
            return false;
        }
        uasort($found, fn ($a, $b) => $a[2] <=> $b[2]);
        $ranked = array_values($found);
        if (count($ranked) > 1 && $ranked[0][2] === $ranked[1][2]) {
            return false; // two equally good explanations: do not guess
        }
        [$k, $x] = $ranked[0];
        $old = $raw[$k]['abs'];
        $raw[$k]['abs'] = $x / 100;
        if ($this->fits($raw, $idx, $delta)) {
            return true;
        }
        $raw[$k]['abs'] = $old;
        return false;
    }

    /**
     * Last resort: the group is short by exactly one transaction OCR never delivered. Insert it
     * (amount recovered from the printed balance) and flag it for review.
     */
    private function placeholderFix(array &$raw, int $i, int $end, float $delta, int &$unparsed, array &$samples): bool
    {
        $idx = range($i, $end);
        $fixed = 0;
        $free = [];
        foreach ($idx as $k) {
            if ($raw[$k]['abs'] === null) {
                continue;
            }
            $c = (int) round($raw[$k]['abs'] * 100);
            $h = (int) $raw[$k]['hint'];
            if ($h !== 0) {
                $fixed += $h * $c;
            } else {
                $free[] = $c;
            }
        }
        if (count($free) > 8) {
            return false;
        }
        $target = (int) round($delta * 100);
        $byCredits = [];
        for ($mask = 0, $n = 1 << count($free); $mask < $n; $mask++) {
            $sum = $fixed;
            $credits = 0;
            foreach ($free as $b => $c) {
                if (($mask >> $b) & 1) {
                    $sum += $c;
                    $credits++;
                } else {
                    $sum -= $c;
                }
            }
            $r = $target - $sum;
            if ($r !== 0) {
                $byCredits[$credits][$r] = true;
            }
        }
        if (!$byCredits) {
            return false;
        }
        ksort($byCredits);
        $cands = reset($byCredits); // the most ordinary reading (fewest credits) decides
        if (count($cands) !== 1) {
            return false;
        }
        $r = (int) array_key_first($cands);
        if (abs($r) > 300000) {
            return false; // far too large to be one missing payment: a balance was misread
        }
        if (preg_match('/^[1-9]0*$/', (string) abs($r))) {
            return false; // one wrong digit somewhere (1.00, 30.00, 1000.00 ...), not a missing transaction
        }
        $anchor = $raw[$end];
        $row = [
            'page' => $anchor['page'], 'date' => $anchor['date'],
            'desc' => 'Unreadable transaction (recovered from running balance)',
            'abs' => abs($r) / 100, 'balance' => null, 'hint' => $r > 0 ? 1 : -1, 'key' => 'vm|unreadable',
        ];
        array_splice($raw, $end, 0, [$row]);
        $unparsed++;
        if (count($samples) < 5) {
            $samples[] = 'Transaction missing from OCR on ' . $anchor['date'] . ' recovered from the running balance: '
                . number_format(abs($r) / 100, 2, '.', '') . ($r > 0 ? ' in' : ' out') . ' - please check';
        }
        return true;
    }

    private function hint(string $desc): int
    {
        if (preg_match('/^(Gross\s+Interest|Interest\s+Paid)\b/i', $desc)
            || preg_match('/\b(Salary|Dividend)\b/i', $desc)
            || preg_match('/^Postcode\s+Lottery\s*$/i', $desc)) {
            return 1;
        }
        // Faster Payment / BACS credits carry the payer's 14-digit reference.
        if (!preg_match('/^(C[il1]s?|Card|Cash|Crd|DD|OD|W[lI1]t)\b/i', $desc)
            && (preg_match('/(?<!\d)\d{12,16}(?!\d)/', $desc) || preg_match('/\b(Faster\s+Paym|DWP)\b/i', $desc))) {
            return 1;
        }
        if (preg_match('/^(C[il1]s?|Card|Cash|Crd\s?\d*|DD|OD|SO|W[lI1]t\d*)\b/i', $desc)) {
            return -1;
        }
        return 0;
    }

    private function key(string $desc): string
    {
        $body = preg_replace('/^(C[il1]s?|Card|Cash|Crd)\s?\d*\s+/i', '', $desc) ?? $desc;
        $body = preg_replace('/[^A-Za-z ].*$/u', '', $body) ?? $body;
        return 'vm|' . mb_strtolower(trim($body));
    }

    /**
     * Read the column values printed on a "Stg Amt" line:
     *
     *   Stg Amt 23.38 | Debits 23.38 | Balance 10657.21
     *
     * OCR flattens that into "Stg Amt 23.38 23.38 1065721". The sterling amount has
     * already been taken as the transaction amount, so what follows is the duplicate
     * Debits column and then the running balance - usually with its decimal point lost.
     *
     * @return array{0:string,1:?float} remaining description, printed balance
     */
    private function fxTail(string $content, float $stg): array
    {
        $tokens = preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $money = [];
        $balance = null;
        $firstNumeric = null;

        foreach ($tokens as $i => $token) {
            if (preg_match('/^-?£?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}$/u', (string) $token)) {
                if ($firstNumeric === null) {
                    $firstNumeric = $i;
                }
                $money[] = $this->toFloat((string) $token);
            } elseif (preg_match('/^\d{4,9}$/u', (string) $token)) {
                // Balance with the decimal point lost: "1065721" -> 10657.21.
                if ($firstNumeric === null) {
                    $firstNumeric = $i;
                }
                $balance = round(((int) $token) / 100, 2);
            }
        }

        // Text after the first column value is OCR spill from the sidebar/glossary.
        $desc = $firstNumeric === null
            ? trim(implode(' ', $tokens))
            : trim(implode(' ', array_slice($tokens, 0, $firstNumeric)));

        if ($balance === null) {
            if (count($money) >= 2) {
                $balance = (float) end($money);
            } elseif (count($money) === 1 && abs($money[0] - $stg) >= 0.005) {
                $balance = $money[0];
            }
        }

        return [$desc, $balance];
    }

    /**
     * A recovered balance must be reachable from the last printed balance plus the
     * rows parsed since then. OCR sometimes turns "1025482" into "2695282"; without
     * this guard that misread becomes a PDF balance and a fake mismatch.
     *
     * @param array<int,array<string,mixed>> $raw
     */
    private function plausibleBalance(float $candidate, array $raw, ?float $opening): bool
    {
        $anchor = $opening;
        $since = 0.0;
        for ($k = count($raw) - 1; $k >= 0; $k--) {
            if ($raw[$k]['balance'] !== null) {
                $anchor = (float) $raw[$k]['balance'];
                break;
            }
            $since += abs((float) $raw[$k]['abs']);
        }
        if ($anchor === null) {
            return true;
        }

        // Allow for rows OCR dropped entirely as well as normal rounding.
        $margin = max(250.0, abs($anchor) * 0.1);
        return abs($candidate - $anchor) <= $since + $margin;
    }

    /**
     * Pull an amount off a line whose printed amount has OCR spill after it:
     * "Aspley 4.75 Financial" / "Aspley 8.80 (FSCS)". trailingMoney() only reads
     * the very end of the line, so a sidebar fragment hides the amount. Strip up
     * to three trailing tokens that contain no digits (panel words / punctuation)
     * and try again. A line without a money token still yields no rows.
     *
     * @return array{0:string,1:array<int,float>}
     */
    private function moneyBeforeJunk(string $content, int $max = 2): array
    {
        $tokens = preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $junk = 0;
        while ($tokens && $junk < 3) {
            $end = (string) end($tokens);
            if (preg_match('/^-?£?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}$/u', $end)) {
                break;
            }
            if (preg_match('/\d/u', $end) || mb_strlen($end) > 24) {
                break;
            }
            array_pop($tokens);
            $junk++;
        }

        if ($junk === 0) {
            return [$content, []];
        }

        return $this->trailingMoney(implode(' ', $tokens), $max);
    }

    /**
     * Recover a transaction whose printed amount is followed by OCR junk: the running balance
     * with its comma/decimal point lost, and/or the next row's day glued onto the end of the
     * line. Returns [description, trailing amounts] or null when the line is not of that shape.
     *
     * @return array{0:string,1:array<int,float>}|null
     */
    private function salvageAmounts(string $content): ?array
    {
        $tokens = preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) < 2) {
            return null;
        }

        // Walk back over the OCR tail: at most three digit-only tokens. A single stray
        // trailing non-digit blob (sidebar text glued onto the row, e.g. "o-Wocaen")
        // may sit behind them and hide the digits - skip exactly one such blob.
        $tail = [];
        $skippedJunk = false;
        while ($tokens && count($tail) < 3) {
            $end = (string) end($tokens);
            if (preg_match(self::OCR_TAIL_TOKEN, $end)) {
                $tail[] = $end;
                array_pop($tokens);
                continue;
            }
            if (!$tail && !$skippedJunk && preg_match('/^[^\d]{1,12}$/u', $end)) {
                array_pop($tokens);
                $skippedJunk = true;
                continue;
            }
            break;
        }
        if (!$tail) {
            return null;
        }
        // A lone short number (a house number, a reference) is not a garbled balance.
        $balanceLike = false;
        foreach ($tail as $tok) {
            if (strlen(strtr($tok, 'OolI', '0011')) >= 4) {
                $balanceLike = true;
                break;
            }
        }

        // Then walk back over the printed amount (and the printed balance, if it kept its point).
        $nums = [];
        while ($tokens && count($nums) < 2) {
            $tok = (string) end($tokens);
            if (!preg_match('/^-?£?(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}$/u', $tok)) {
                break;
            }
            array_unshift($nums, $this->toFloat($tok));
            array_pop($tokens);
        }
        if (!$nums || (!$balanceLike && count($nums) < 2)) {
            return null;
        }

        // The OCR tail may hold the running balance with its decimal point lost: "1033811" -> 10338.11.
        if (count($nums) < 2 && $balanceLike) {
            foreach ($tail as $tok) {
                $norm = strtr($tok, 'OolIQ', '00110');
                if (strlen($norm) >= 4 && ctype_digit($norm)) {
                    $nums[] = round(((int) $norm) / 100, 2);
                    break;
                }
            }
        }

        $desc = trim(implode(' ', $tokens));
        if ($desc === '') {
            return null;
        }
        return [$desc, $nums];
    }

    /**
     * OCR sometimes reads a decimal point in amounts as a comma ("12,25" for £12.25).
     * Only a comma followed by exactly two decimal digits and not a longer digit run
     * (i.e. never a thousands separator like "1,234" or "1,234.56") is converted.
     */
    private function normalizeDecimalCommas(string $line): string
    {
        return preg_replace('/(?<![\w])(\d{1,3}),(\d{2})(?![\d.])/u', '$1.$2', $line) ?? $line;
    }

    private function isNoise(string $line): bool
    {
        foreach (self::NOISE as $re) {
            if (preg_match($re, $line)) {
                return true;
            }
        }
        return $this->isPanelFragment($line);
    }

    /** A line made up only of right-hand panel wording ("Co in this account are"). */
    private function isPanelFragment(string $line): bool
    {
        $words = preg_split('/[^A-Za-z]+/', mb_strtolower($line), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) < 2) {
            return false;
        }
        foreach ($words as $word) {
            if (!isset(self::PANEL_WORDS[$word])) {
                return false;
            }
        }
        return true;
    }
}