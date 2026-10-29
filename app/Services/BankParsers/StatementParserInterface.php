<?php

namespace App\Services\BankParsers;

interface StatementParserInterface
{
    public function canHandle(string $text, array $meta = []): bool;

    /**
     * Parse a whole statement (all pages, keyed by page number).
     *
     * @param  array<int,string> $pages
     * @return array{rows:array<int,array>,unparsed:int,samples:array<int,string>,opening:?float,closing:?float,total_in:?float,total_out:?float}
     */
    public function parseDocument(array $pages, bool $fromOcr = false): array;

    /** Single page convenience wrapper. */
    public function parse(string $text, int $pageNo = 1, bool $fromOcr = false): array;

    public function name(): string;
}
