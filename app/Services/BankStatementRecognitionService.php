<?php
namespace App\Services;

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
        try { return (new Parser())->parseFile($path)->getText(); }
        catch (\Throwable $e) { return ''; }
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
        if (mb_strlen(trim($embedded))>50) return $embedded;

        $ocrText=$this->ocrViaTesseract($path);
        if (mb_strlen(trim($ocrText))>mb_strlen(trim($embedded))) {
            return $ocrText;
        }
        return $embedded;
    }

    private function ocrViaTesseract(string $path): string
    {
        // Reuse the invoice/receipt OCR toolchain (Ghostscript + Tesseract) so
        // scanned bank statements work wherever receipt OCR already works.
        try {
            return app(InvoiceExtractorService::class)->extractRawText($path);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function detect(string $text, array $accounts): array
    {
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
        $lastFour = null;
        // Only trust digits marked as account details; do NOT scan all arbitrary numbers.
        if (preg_match('/(?:account\s*(?:number|no\.?|#)|a\/c\s*(?:no\.?|number))\s*[:#-]?\s*(?:[0-9*•xX\s-]{4,20})/iu', $text, $m)) {
            $digits = preg_replace('/\D/', '', $m[0]);
            if (strlen($digits) >= 4) $lastFour = substr($digits, -4);
        }
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
        return ['detected_bank'=>$bank, 'detected_account_last_four'=>$lastFour,
            'confidence'=>$suggested ? 'high' : ($bank ? 'review' : 'unknown'),
            'suggested_bank_account_id'=>$suggested, 'candidates'=>$matches,
            'requires_confirmation'=>true,
            'note'=>'Confirm the bank account before importing. PDF extraction is not proof of ownership.'];
    }
}
