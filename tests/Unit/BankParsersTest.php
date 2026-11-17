<?php

namespace Tests\Unit;

use App\Services\BankParsers\LloydsParser;
use App\Services\BankParsers\StarlingParser;
use App\Services\BankParsers\VirginMoneyParser;
use PHPUnit\Framework\TestCase;

/**
 * Statement layouts where the bank prints the date once per day (first row only).
 * Later rows on the same day have no date column and must inherit the printed one.
 */
class BankParsersTest extends TestCase
{
    private const POUND = "\u{00A3}";

    /** @return array<int,array<string,mixed>> */
    private function rows(array $result): array
    {
        return $result['rows'];
    }

    public function test_lloyds_same_day_rows_inherit_the_printed_date(): void
    {
        $parsed = (new LloydsParser())->parseDocument([1 => implode("\n", [
            'Lloyds Bank',
            '01 Apr 25 STATEMENT OPENING BALANCE 1,000.00',
            '03 Apr 25 DEB TESCO STORES 12.34 987.66',
            'DEB SHELL 40.00 947.66',
            'DEB COSTA 5.50 942.16',
            '30 Apr 25 STATEMENT CLOSING BALANCE 57.84 0.00 942.16',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['2025-04-03', '2025-04-03', '2025-04-03'], array_column($rows, 'transaction_date'));
        $this->assertSame([-12.34, -40.0, -5.5], array_map('floatval', array_column($rows, 'amount')));
        $this->assertSame(0, $parsed['unparsed']);
    }

    public function test_lloyds_same_day_rows_with_the_amount_on_the_next_line(): void
    {
        $parsed = (new LloydsParser())->parseDocument([1 => implode("\n", [
            'Lloyds Bank',
            '01 Apr 25 STATEMENT OPENING BALANCE 1,000.00',
            '03 Apr 25 DEB TESCO STORES',
            '12.34 987.66',
            'DEB SHELL',
            '40.00 947.66',
            'DEB COSTA',
            '5.50 942.16',
            '30 Apr 25 STATEMENT CLOSING BALANCE 57.84 0.00 942.16',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['DEB TESCO STORES', 'DEB SHELL', 'DEB COSTA'], array_column($rows, 'description'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** A wrapped reference line starting with a payment type is not a new transaction. */
    public function test_lloyds_wrapped_reference_starting_with_a_type_token(): void
    {
        $parsed = (new LloydsParser())->parseDocument([1 => implode("\n", [
            'Lloyds Bank',
            '01 Apr 25 STATEMENT OPENING BALANCE 1,000.00',
            '03 Apr 25 DEB TESCO STORES 12.34 987.66',
            'SO REF 12345',
            '04 Apr 25 FPI SALARY 2,000.00 2,987.66',
            '30 Apr 25 STATEMENT CLOSING BALANCE 12.34 2,000.00 2,987.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(2, $rows);
        $this->assertStringContainsString('SO REF 12345', $rows[0]['description']);
        $this->assertSame(0, $parsed['unparsed']);
    }

    public function test_lloyds_date_only_line_starts_a_new_day(): void
    {
        $parsed = (new LloydsParser())->parseDocument([1 => implode("\n", [
            'Lloyds Bank',
            '01 Apr 25 STATEMENT OPENING BALANCE 1,000.00',
            '03 Apr 25',
            'DEB TESCO STORES 12.34 987.66',
            'DEB SHELL 40.00 947.66',
            '30 Apr 25 STATEMENT CLOSING BALANCE 52.34 0.00 947.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(2, $rows);
        $this->assertSame(['2025-04-03', '2025-04-03'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** A day can continue onto the next page after the repeated header. */
    public function test_lloyds_carried_date_survives_a_page_break(): void
    {
        $parsed = (new LloydsParser())->parseDocument([
            1 => implode("\n", [
                'Lloyds Bank',
                '01 Apr 25 STATEMENT OPENING BALANCE 1,000.00',
                '03 Apr 25 DEB TESCO STORES 12.34 987.66',
            ]),
            2 => implode("\n", [
                'Date Pmnt Details Money out Money in Balance',
                'DEB SHELL 40.00 947.66',
                'DEB COSTA 5.50 942.16',
                '30 Apr 25 STATEMENT CLOSING BALANCE 57.84 0.00 942.16',
            ]),
        ], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['2025-04-03', '2025-04-03', '2025-04-03'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    public function test_starling_same_day_rows_inherit_the_printed_date(): void
    {
        $p = self::POUND;
        $parsed = (new StarlingParser())->parseDocument([1 => implode("\n", [
            'Starling Bank',
            "OPENING BALANCE {$p}1,000.00",
            "01/04/2025 FASTER PAYMENT TESCO {$p}12.34 {$p}987.66",
            "FASTER PAYMENT SHELL {$p}40.00 {$p}947.66",
            "CARD PAYMENT COSTA {$p}5.50 {$p}942.16",
            "02/04/2025 DIRECT CREDIT SALARY {$p}2,000.00 {$p}2,942.16",
            "Closing Balance {$p}2,942.16",
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(4, $rows);
        $this->assertSame(
            ['2025-04-01', '2025-04-01', '2025-04-01', '2025-04-02'],
            array_column($rows, 'transaction_date')
        );
        $this->assertSame(0, $parsed['unparsed']);
    }

    public function test_virgin_parses_amounts_with_pound_signs(): void
    {
        $p = self::POUND;
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 05 April 2025',
            "Opening balance {$p}1,000.00",
            'Date Description Debits Credits Balance',
            "03 Apr TESCO STORES {$p}12.34 {$p}987.66",
            "SHELL {$p}40.00 {$p}947.66",
            "04 Apr SALARY {$p}2,000.00 {$p}2,947.66",
            "Closing balance {$p}2,947.66",
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['2025-04-03', '2025-04-03', '2025-04-04'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** The repeated "Date Description" header is absent on continuation pages. */
    public function test_virgin_continuation_page_without_the_header(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([
            1 => implode("\n", [
                'Virgin Money',
                'Statement date 05 April 2025',
                'Opening balance 1,000.00',
                'Date Description Debits Credits Balance',
                '03 Apr TESCO STORES 12.34 987.66',
            ]),
            2 => implode("\n", [
                'SHELL 40.00 947.66',
                'COSTA 5.50 942.16',
                'Closing balance 942.16',
                'Ready, get set, go',
                'SALARY 2,000.00 2,942.16',
            ]),
        ], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['2025-04-03', '2025-04-03', '2025-04-03'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** A January statement covering December must not push December into the following year. */
    public function test_virgin_year_rolls_back_to_the_statement_date(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 05 January 2026',
            'Opening balance 1,000.00',
            'Date Description Debits Credits Balance',
            '20 Dec TESCO 12.34 987.66',
            '24 Dec SHELL 40.00 947.66',
            '02 Jan SALARY 2,000.00 2,947.66',
            'Closing balance 2,947.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertSame(['2025-12-20', '2025-12-24', '2026-01-02'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }
}
