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
     * Extract text from a PDF, falling back to OCR when embedded text is missing
     * (scanned / image-based statements). Tesseract must be installed on the
     * server for the OCR path to work; otherwise returns whatever embedded text
     * is available (possibly empty).
     */
    public function extractPdfTextWithOcr(string $path): string
    {
        $embedded=$this->extractPdfText($path);
        $embeddedLen = mb_strlen(trim($embedded));
        Log::info('PDF embedded text length', ['path' => $path, 'length' => $embeddedLen]);
        if ($embeddedLen > 50) {
            Log::info('Using embedded PDF text (no OCR needed)', ['path' => $path]);
            return $embedded;
        }
        Log::info('Embedded text too short, falling back to OCR via Ghostscript + Tesseract', ['path' => $path, 'embedded_length' => $embeddedLen]);
        $ocrText = $this->ocrViaTesseract($path);
        $ocrLen = mb_strlen(trim($ocrText));
        Log::info('OCR result via Tesseract/Ghostscript', ['path' => $path, 'ocr_length' => $ocrLen]);
        if ($ocrLen > $embeddedLen) {
            Log::info('Using OCR text over embedded', ['path' => $path]);
            return $ocrText;
        }
        Log::warning('OCR did not improve over embedded text', ['path' => $path, 'embedded_length' => $embeddedLen, 'ocr_length' => $ocrLen]);
        return $embedded;
    }

    private function ocrViaTesseract(string $path): string
    {
        Log::info('Starting OCR with Ghostscript + Tesseract', ['path' => $path]);
        try {
            $text = app(InvoiceExtractorService::class)->extractRawText($path);
            Log::info('OCR completed via InvoiceExtractorService (Ghostscript -> PNG -> Tesseract)', ['path' => $path, 'length' => mb_strlen($text)]);
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
        $header = mb_strtolower(mb_substr($text, 0, 4500));
        $found = [];
        foreach (self::BANKS as $bank => $signals) {
            foreach ($signals as $signal) {
                if (preg_match('/(?<![\pL])'.preg_quote($signal, '/').'(?![\pL])/iu', $header)) {
                    $found[] = $bank; break;
                }
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
