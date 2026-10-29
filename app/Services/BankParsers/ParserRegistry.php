<?php

namespace App\Services\BankParsers;

class ParserRegistry
{
    /** @return array<int,StatementParserInterface> */
    public static function all(): array
    {
        return [new StarlingParser(), new LloydsParser()];
    }

    public static function detect(string $text): ?StatementParserInterface
    {
        foreach (self::all() as $parser) {
            if ($parser->canHandle($text)) {
                return $parser;
            }
        }
        return null;
    }
}
