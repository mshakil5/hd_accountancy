<?php

namespace App\Services\BankParsers;

/**
 * Bank-specific statement parsers.
 * Each parser is responsible for one bank's layout and returns
 * the same row shape that BankStatementVerificationService expects.
 */
interface StatementParserInterface
{
    /**
     * Whether this parser can handle the given text + detection metadata.
     */
    public function canHandle(string $text, array $meta = []): bool;

    /**
     * Parse one page (or whole-document blob) into transaction rows.
     *
     * @return array{rows: array<int,array>, unparsed: int, samples: array<int,string>}
     */
    public function parse(string $text, int $pageNo = 1, bool $fromOcr = false): array;

    /**
     * Human-readable name for logging / UI.
     */
    public function name(): string;
}
