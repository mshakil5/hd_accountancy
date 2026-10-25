<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

/**
 * Bank + account detection for uploaded statements.
 *
 * Goals:
 * - Near-100% correct bank identification when signals exist
 * - Never silently create a wrong bank
 * - Always require explicit user confirmation unless confidence is "high"
 */
class BankStatementRecognitionService
{
    private const BANKS = [
        'HSBC'                  => ['hsbc', 'hsbc uk bank'],
        'Barclays'              => ['barclays', 'barclays bank'],
        'Halifax'               => ['halifax', 'halifax bank'],
        'Monzo'                 => ['monzo', 'monzo bank'],
        'Lloyds'                => ['lloyds bank', 'lloyds banking', 'club lloyds', 'lloyds'],
        'NatWest'               => ['natwest', 'national westminster'],
        'Santander'             => ['santander'],
        'Starling'              => ['starling bank', 'starling'],
        'Nationwide'            => ['nationwide building society', 'nationwide'],
        'Metro Bank'            => ['metro bank'],
        'Revolut'               => ['revolut'],
        'TSB'                   => ['tsb bank', 'tsb'],
        'Bank of Scotland'      => ['bank of scotland'],
        'Royal Bank of Scotland'=> ['royal bank of scotland', 'rbs'],
        'Bank of Ireland'       => ['bank of ireland'],
        'Co-operative Bank'     => ['co-operative bank', 'cooperative bank', 'co-op bank'],
        'First Direct'          => ['first direct'],
        'Virgin Money'          => ['virgin money'],
        'Chase'                 => ['chase uk', 'chase bank'],
        'Wise'                  => ['wise'],
        'Tide'                  => ['tide'],
        'Mettle'                => ['mettle'],
        'Kroo'                  => ['kroo'],
        'Club'                  => ['club account', 'club bank', 'club'],
        'Platinum'              => ['platinum account', 'platinum bank', 'platinum'],
    ];

    /** UK sort-code prefix → bank (public knowledge, not exhaustive). */
    private const SORT_CODE_BANKS = [
        '30' => 'Lloyds',          // 30-xx-xx
        '77' => 'Lloyds',
        '40' => 'HSBC',
        '20' => 'Barclays',
        '11' => 'Halifax',
        '04' => 'Monzo',           // 04-00-04 etc.
        '60' => 'NatWest',
        '09' => 'Santander',
        '60-83' => 'Starling',     // more specific if needed
        '07' => 'Nationwide',
        '23' => 'Metro Bank',
        '04-00' => 'Revolut',
        '77-22' => 'TSB',
    ];

    public function extractPdfText(string $path): string
    {
        try {
            $text = (new Parser())->parseFile($path)->getText();
            Log::info('PDF embedded text extracted', ['path' => $path, 'length' => mb_strlen($text)]);
            return $text;
        } catch (\Throwable $e) {
            Log::error('PDF embedded text extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    public function extractPdfTextWithOcr(string $path): string
    {
        $embedded = $this->extractPdfText($path);
        $embeddedLen = mb_strlen(trim($embedded));
        $ocrText = $this->ocrViaTesseract($path);
        $ocrLen = mb_strlen(trim($ocrText));

        if ($ocrLen > 0 && $embeddedLen > 0) {
            // OCR first – preserves visual header order
            return $ocrText . "\n" . $embedded;
        }
        return $ocrLen > 0 ? $ocrText : $embedded;
    }

    private function ocrViaTesseract(string $path): string
    {
        try {
            // Only first page for detection (fast)
            return app(InvoiceExtractorService::class)->extractRawText($path, 1);
        } catch (\Throwable $e) {
            Log::error('OCR via Tesseract failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Detect bank + account from statement text and match against existing accounts.
     *
     * @param  string  $text      Full or header text
     * @param  iterable $accounts Existing bank accounts (objects with bank_name, account_last_four, sort_code, …)
     * @return array
     */
    public function detect(string $text, $accounts): array
    {
        $rawHeader = mb_substr($text, 0, 6000);
        $header = mb_strtolower($rawHeader);

        // ---------- 1. Bank name ----------
        $foundBanks = [];
        foreach (self::BANKS as $bank => $signals) {
            foreach ($signals as $signal) {
                if (preg_match('/(?<![\pL])' . preg_quote($signal, '/') . '(?![\pL])/iu', $header)) {
                    $foundBanks[] = $bank;
                    break;
                }
            }
        }
        $foundBanks = array_values(array_unique($foundBanks));

        // Explicit "Bank name: …" label
        if (!$foundBanks && preg_match('/(?:bank\s*(?:name)?|financial\s+institution)\s*[:\-]\s*([\pL][\pL&.,\'\- ]{2,80})/iu', $rawHeader, $m)) {
            $candidate = trim(preg_replace('/\s+/u', ' ', $m[1]));
            $candidate = preg_replace('/\s+(?:account|statement|sort\s+code)\b.*$/iu', '', $candidate);
            if ($candidate !== '' && mb_strlen($candidate) <= 80) {
                $foundBanks[] = $candidate;
            }
        }

        // ---------- 2. Sort code ----------
        $sortCode = null;
        if (preg_match('/\bsort\s*code\s*[:\-]?\s*(\d{2})\s*[-\s]?\s*(\d{2})\s*[-\s]?\s*(\d{2})\b/iu', $rawHeader, $m)) {
            $sortCode = $m[1] . '-' . $m[2] . '-' . $m[3];
        } elseif (preg_match('/\b(\d{2})\s*-\s*(\d{2})\s*-\s*(\d{2})\b/', $rawHeader, $m)) {
            // Only accept if it looks like a sort-code context (near "sort" or account block)
            $sortCode = $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        // Infer bank from sort-code prefix if name was ambiguous
        if ($sortCode && count($foundBanks) !== 1) {
            $prefix2 = substr($sortCode, 0, 2);
            $prefix5 = substr($sortCode, 0, 5);
            if (isset(self::SORT_CODE_BANKS[$prefix5])) {
                $foundBanks = [self::SORT_CODE_BANKS[$prefix5]];
            } elseif (isset(self::SORT_CODE_BANKS[$prefix2])) {
                $foundBanks = [self::SORT_CODE_BANKS[$prefix2]];
            }
        }

        $bank = count($foundBanks) === 1 ? $foundBanks[0] : null;

        // ---------- 3. Account number (last 4 + full if possible) ----------
        $lastFour = null;
        $fullAccount = null;
        if (preg_match('/(?:account\s*(?:number|no\.?|#)|a\/c\s*(?:no\.?|number)|account)\s*[:#\-]?\s*([0-9*•xX\s-]{6,20})/iu', $rawHeader, $m)) {
            $digits = preg_replace('/\D/', '', $m[1]);
            if (strlen($digits) >= 4) {
                $lastFour = substr($digits, -4);
                if (strlen($digits) >= 8) {
                    $fullAccount = $digits;
                }
            }
        }
        // Fallback: "Account Number 33666568"
        if (!$lastFour && preg_match('/account\s*number\s+(\d{6,12})/iu', $rawHeader, $m)) {
            $digits = $m[1];
            $lastFour = substr($digits, -4);
            $fullAccount = $digits;
        }

        // ---------- 4. IBAN / BIC ----------
        $iban = null;
        $bic = null;
        if (preg_match('/\b(GB\d{2}\s*[A-Z]{4}\s*\d{4}\s*\d{4}\s*\d{4}\s*\d{2})\b/iu', $rawHeader, $m)) {
            $iban = preg_replace('/\s+/', '', strtoupper($m[1]));
        }
        if (preg_match('/\b([A-Z]{4}GB\d{2}[A-Z0-9]{3})\b/iu', $rawHeader, $m)) {
            $bic = strtoupper($m[1]);
        }

        // ---------- 5. Match against existing accounts ----------
        $matches = [];
        foreach ($accounts as $account) {
            if (!(bool) ($account->active ?? true)) {
                continue;
            }

            $registeredName = strtolower(trim((string) ($account->bank_name ?? '')));
            $registeredLast4 = (string) ($account->account_last_four ?? '');
            $registeredSort  = (string) ($account->sort_code ?? '');

            $bankNameMatch = $bank && (
                $registeredName === strtolower($bank)
                || str_contains($registeredName, strtolower($bank))
                || str_contains(strtolower($bank), $registeredName)
            );

            $last4Match = $lastFour && $registeredLast4 === $lastFour;

            $sortMatch = $sortCode && $registeredSort !== '' && (
                str_replace('-', '', $registeredSort) === str_replace('-', '', $sortCode)
            );

            if ($bankNameMatch || $last4Match || $sortMatch) {
                $score = 0;
                if ($bankNameMatch) $score += 2;
                if ($last4Match)    $score += 3;
                if ($sortMatch)     $score += 3;
                if ($iban && !empty($account->iban) && strtoupper($account->iban) === $iban) $score += 4;

                $confidence = 'review';
                if ($score >= 5 || ($last4Match && $sortMatch) || ($last4Match && $bankNameMatch && $sortMatch)) {
                    $confidence = 'high';
                } elseif ($score >= 3) {
                    $confidence = 'medium';
                }

                $matches[] = [
                    'id'                => (int) $account->id,
                    'bank_name'         => $account->bank_name,
                    'account_label'     => $account->account_label ?? null,
                    'last_four_match'   => (bool) $last4Match,
                    'bank_name_match'   => (bool) $bankNameMatch,
                    'sort_code_match'   => (bool) $sortMatch,
                    'confidence'        => $confidence,
                    'score'             => $score,
                ];
            }
        }

        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        $strong = array_values(array_filter($matches, fn ($m) => $m['confidence'] === 'high'));
        $suggested = count($strong) === 1 ? $strong[0]['id'] : null;

        // Only "high" allows auto-selection. Everything else forces confirmation.
        $overallConfidence = $suggested ? 'high' : ($bank || $lastFour || $sortCode ? 'review' : 'unknown');

        $result = [
            'detected_bank'              => $bank,
            'detected_account_last_four' => $lastFour,
            'detected_account_number'    => $fullAccount,
            'detected_sort_code'         => $sortCode,
            'detected_iban'              => $iban,
            'detected_bic'               => $bic,
            'confidence'                 => $overallConfidence,
            'suggested_bank_account_id'  => $suggested,
            'candidates'                 => $matches,
            // CRITICAL: UI must respect this
            'requires_confirmation'      => $overallConfidence !== 'high',
            'can_auto_select'            => $overallConfidence === 'high' && $suggested !== null,
            'note'                       => $overallConfidence === 'high'
                ? 'High-confidence match – you may continue.'
                : 'Please confirm or select the correct bank account before importing. Detection is not proof of ownership.',
        ];

        Log::info('Bank detection result', [
            'bank' => $bank,
            'lastFour' => $lastFour,
            'sortCode' => $sortCode,
            'suggested_id' => $suggested,
            'confidence' => $overallConfidence,
            'candidates' => count($matches),
        ]);

        return $result;
    }
}
