<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

/** Conservative metadata detection; never interprets OCR guesses as verified identities. */
class BankStatementRecognitionService
{
    private const BANKS = [
        'HSBC' => ['hsbc', 'hsbc uk bank'],
        'Barclays' => ['barclays', 'barclays bank'],
        'Halifax' => ['halifax', 'halifax bank'],
        'Monzo' => ['monzo', 'monzo bank'],
        'Lloyds' => ['lloyds bank', 'lloyds banking'],
        'NatWest' => ['natwest', 'national westminster'],
        'Santander' => ['santander'],
        'Starling' => ['starling bank'],
        'Nationwide' => ['nationwide building society'],
        'Metro Bank' => ['metro bank'],
        'Revolut' => ['revolut'],
        'TSB' => ['tsb bank'],
        'Bank of Scotland' => ['bank of scotland'],
        'Royal Bank of Scotland' => ['royal bank of scotland'],
        'Bank of Ireland' => ['bank of ireland'],
        'Co-operative Bank' => ['co-operative bank', 'cooperative bank'],
        'First Direct' => ['first direct'],
        'Virgin Money' => ['virgin money'],
        'Chase' => ['chase uk', 'chase bank'],
        'Wise' => ['wise'],
        'Tide' => ['tide'],
        'Mettle' => ['mettle'],
        'Kroo' => ['kroo'],
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

    /**
     * Extract text from a PDF with both its embedded text and OCR.  Bank logos
     * and header labels are frequently rendered as images even in otherwise
     * text-based PDFs, so OCR must not be limited to scanned statements.
     */
    public function extractPdfTextWithOcr(string $path): string
    {
        $embedded=$this->extractPdfText($path);
        $embeddedLen = mb_strlen(trim($embedded));
        Log::info('PDF embedded text length', ['path' => $path, 'length' => $embeddedLen]);
        Log::info('Reading bank-statement header with Ghostscript + Tesseract', ['path' => $path, 'embedded_length' => $embeddedLen]);
        $ocrText = $this->ocrViaTesseract($path);
        $ocrLen = mb_strlen(trim($ocrText));
        Log::info('OCR result via Tesseract/Ghostscript', ['path' => $path, 'ocr_length' => $ocrLen]);
        if ($ocrLen > 0 && $embeddedLen > 0) {
            // OCR first: it preserves visual header order, while the PDF text
            // layer often starts with a transaction table or has no logo text.
            return $ocrText . "\n" . $embedded;
        }
        return $ocrLen > 0 ? $ocrText : $embedded;
    }

    private function ocrViaTesseract(string $path): string
    {
        Log::info('Starting OCR with Ghostscript + Tesseract', ['path' => $path]);
        try {
            // The issuing bank and account header are on page one. OCRing a
            // whole statement here can exceed an HTTP request timeout, while
            // full-document OCR remains available for scanned-row extraction.
            $text = app(InvoiceExtractorService::class)->extractRawText($path, 1);
            Log::info('First-page OCR completed via InvoiceExtractorService (Ghostscript -> PNG -> Tesseract)', ['path' => $path, 'length' => mb_strlen($text)]);
            return $text;
        } catch (\Throwable $e) {
            Log::error('OCR via Tesseract/InvoiceExtractorService failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    public function detect(string $text, array $accounts): array
    {
        Log::info('Starting bank detection', ['text_length' => mb_strlen($text), 'accounts_count' => count($accounts)]);
        // Prefer the statement header; whole-document scanning can confuse payees with issuer.
        $rawHeader = mb_substr($text, 0, 4500);
        $header = mb_strtolower($rawHeader);
        $found = [];
        foreach (self::BANKS as $bank => $signals) {
            foreach ($signals as $signal) {
                if (preg_match('/(?<![\pL])'.preg_quote($signal, '/').'(?![\pL])/iu', $header)) {
                    $found[] = $bank; break;
                }
            }
        }
        // Statements from banks that are not in the built-in list often print
        // their legal name in the header. Accept that only when it is explicitly
        // labelled as the bank name, never from arbitrary transaction text.
        if (!$found && preg_match('/(?:bank\s*(?:name)?|financial\s+institution)\s*[:\-]\s*([\pL][\pL&.,\'\- ]{2,80})/iu', $rawHeader, $m)) {
            $candidate = trim(preg_replace('/\s+/u', ' ', $m[1]));
            $candidate = preg_replace('/\s+(?:account|statement|sort\s+code)\b.*$/iu', '', $candidate);
            if ($candidate !== '' && mb_strlen($candidate) <= 80) {
                $found[] = $candidate;
            }
        }
        $bank = count($found) === 1 ? $found[0] : null;
        Log::info('Bank signals found in header', ['found' => $found, 'bank' => $bank]);
        $lastFour = null;
        // Only trust digits marked as account details; do NOT scan all arbitrary numbers.
        if (preg_match('/(?:account\s*(?:number|no\.?|#)|a\/c\s*(?:no\.?|number))\s*[:#-]?\s*(?:[0-9*•xX\s-]{4,20})/iu', $text, $m)) {
            $digits = preg_replace('/\D/', '', $m[0]);
            if (strlen($digits) >= 4) $lastFour = substr($digits, -4);
        }
        Log::info('Account number detection', ['lastFour' => $lastFour, 'match_found' => isset($m) ? true : false]);
        $matches = [];
        foreach ($accounts as $account) {
            if (!(bool)($account->active ?? true)) continue;
            $registered = strtolower(trim((string)$account->bank_name));
            $bankMatches = $bank && ($registered === strtolower($bank) || str_contains($registered, strtolower($bank)));
            $digitsMatch = $lastFour && (string)($account->account_last_four ?? '') === $lastFour;
            if ($bankMatches || $digitsMatch) {
                $matches[] = ['id' => (int)$account->id, 'bank_name' => $account->bank_name,
                    'account_label' => $account->account_label, 'last_four_match' => (bool)$digitsMatch,
                    'bank_name_match' => (bool)$bankMatches,
                    'confidence' => $bankMatches && $digitsMatch ? 'high' : 'review'];
            }
        }
        usort($matches, fn($a,$b) => (($b['last_four_match'] && $b['bank_name_match']) <=> ($a['last_four_match'] && $a['bank_name_match'])));
        $strong = array_values(array_filter($matches, fn($m) => $m['confidence'] === 'high'));
        $suggested = count($strong) === 1 ? $strong[0]['id'] : null;
        $result = ['detected_bank'=>$bank, 'detected_account_last_four'=>$lastFour,
            'confidence'=>$suggested ? 'high' : ($bank ? 'review' : 'unknown'),
            'suggested_bank_account_id'=>$suggested, 'candidates'=>$matches,
            'requires_confirmation'=>true,
            'note'=>'Confirm the bank account before importing. PDF extraction is not proof of ownership.'];
        Log::info('Bank detection result', ['bank' => $bank, 'lastFour' => $lastFour, 'suggested_id' => $suggested, 'confidence' => $result['confidence'], 'candidates_count' => count($matches)]);
        return $result;
    }
}
