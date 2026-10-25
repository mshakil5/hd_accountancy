<?php

/**
 * PATCH / INTEGRATION GUIDE for BankStatementVerificationService
 *
 * Do NOT replace the whole file. Apply the changes below to your existing
 * BankStatementVerificationService.php.
 *
 * GOAL: Route each statement to a bank-specific parser (Lloyds first)
 *       while keeping all existing validation, balance checks, and status logic.
 */

namespace App\Services;

use App\Services\BankParsers\StatementParserInterface;
use App\Services\BankParsers\LloydsParser;
use App\Services\BankParsers\GenericParser;
// add more parsers later: MonzoParser, StarlingParser, …

// ---------------------------------------------------------------------------
// 1. Add this property + constructor (or resolve via container)
// ---------------------------------------------------------------------------

/*
class BankStatementVerificationService
{
    /** @var StatementParserInterface[] */
    private array $parsers;

    public function __construct()
    {
        // Order matters – first matching parser wins
        $this->parsers = [
            new LloydsParser(),
            // new MonzoParser(),
            // new StarlingParser(),
            new GenericParser(),   // always last
        ];
    }
*/

// ---------------------------------------------------------------------------
// 2. Replace the body of analyse() so it chooses a parser
// ---------------------------------------------------------------------------

/*
public function analyse(string $path): array
{
    $parsedPages = [];
    try {
        foreach ((new \Smalot\PdfParser\Parser())->parseFile($path)->getPages() as $index => $page) {
            $parsedPages[$index + 1] = (string) $page->getText();
        }
    } catch (\Throwable $e) {
        $parsedPages = [];
    }

    $pagesTotal = max(1, count($parsedPages));
    $embedded = trim(implode("\n", $parsedPages));
    $ocr = false;

    if (mb_strlen($embedded) < 50) {
        $ocrText = trim((string) app(InvoiceExtractorService::class)->extractRawText($path));
        if (mb_strlen($ocrText) > mb_strlen($embedded)) {
            $ocr = true;
            $parsedPages = [1 => $ocrText];
        }
    }
    if (!$parsedPages) {
        $parsedPages = [1 => ''];
    }

    // ---- NEW: choose best parser from full text ----
    $fullText = implode("\n", $parsedPages);
    $parser = $this->resolveParser($fullText);
    \Log::info('Statement parser selected', ['parser' => $parser->name()]);

    $rows = [];
    $unparsed = 0;
    $samples = [];
    $pages = [];

    foreach ($parsedPages as $pageNo => $text) {
        $parsed = $parser->parse($text, (int) $pageNo, $ocr);
        $rows = array_merge($rows, $parsed['rows']);
        $unparsed += $parsed['unparsed'];
        $samples = array_merge($samples, $parsed['samples']);
        $pages[(int) $pageNo] = [
            'text_length' => mb_strlen(trim($text)),
            'transactions_detected' => $this->looksLikeTransactions($text),
            'rows' => count($parsed['rows']),
            'ocr_unmapped' => $ocr,
            'parser' => $parser->name(),
        ];
    }

    // … keep the rest of your existing analyse() logic unchanged
    // (opening/closing balance, derived balances, return structure, etc.)
}
*/

// ---------------------------------------------------------------------------
// 3. Add this private helper
// ---------------------------------------------------------------------------

/*
private function resolveParser(string $text): StatementParserInterface
{
    $meta = []; // you can pass detection results if you already have them
    foreach ($this->parsers as $parser) {
        if ($parser->canHandle($text, $meta)) {
            return $parser;
        }
    }
    // should never happen because GenericParser always returns true
    return new GenericParser();
}
*/

// ---------------------------------------------------------------------------
// 4. Optional: when OCR is used, also try the specialised parser on the
//    OCR blob and keep the result that produces more continuous balances.
// ---------------------------------------------------------------------------
