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

    /**
     * Virgin Money MYR foreign-currency transactions with the right-hand sidebar text
     * (Opening balance, Debits, Credits, Closing balance, FSCS notice, abbreviation glossary)
     * interleaved line-by-line as OCR would produce from a two-column PDF.
     *
     * Expected behaviour:
     *  - Every "Cls 77 Myr …" block is a SINGLE transaction whose Sterling amount
     *    comes from the "Stg Amt X.XX" line.
     *  - Sidebar amounts (£10688.17, £3736.20, £0.85, £6952.82) must NEVER appear
     *    as transaction amounts or running balances.
     *  - The correct opening balance (10688.17) is read from the statement summary.
     *  - 0 unparsed rows.
     */
    public function test_virgin_myr_transactions_with_sidebar_noise_interleaved(): void
    {
        // Sidebar summary totals:  Debits=43.21  Credits=0.00  Closing=10644.96
        // Running balances after each tx: 10685.96 → 10682.80 → 10659.42 → 10657.21 → 10644.96
        // These must NOT leak into transaction amounts or fake balances.
        $page1 = implode("\n", [
            'Virgin Money',
            'Statement date 30 January 2026',
            'Date Description Debits Credits Balance',
            'Account number 34085729',
            '31 Dec 2025 Previous statement 10688.17',
            'IBAN GB25YORK05042434085729',
            '02 Jan Cls 77 Myr 12.00',
            'BIC YORKGB21424',
            'Rate 0.18416',
            'Deposits in this account are eligible',
            'for cover under the Financial Services',
            'Compensation Scheme (FSCS)',
            'Stg Amt 2.21 2.21',
            'Cls 77 Myr 17.20',
            'Opening balance',
            'Rate 0.18372',
            '£10688.17',
            'Stg Amt 3.16 3.16',
            'Debits',
            'Cls 77 Myr 127.20',
            '£43.21',
            'Rate 0.18380',
            'Credits',
            'Stg Amt 23.38 23.38 10659.42',
            '£0.00',
            'DD = Direct Debit',
            '05 Jan Cls 77 Myr 12.00',
            'SO = Standing Order',
            'Rate 0.18416',
            'Closing balance',
            'Stg Amt 2.21 2.21',
            '£10644.96',
            'DD Postcode Lottery 12.25 10644.96',
        ]);

        $parsed = (new VirginMoneyParser())->parseDocument([1 => $page1], false);
        $rows   = $this->rows($parsed);

        // Should be exactly 5 transactions: 3 on 02-Jan + 1 on 05-Jan + 1 DD
        $this->assertCount(5, $rows, 'Expected 5 rows; sidebar amounts must not become extra rows');

        // All amounts must be small sterling values, never sidebar summary totals
        $amounts = array_map('floatval', array_column($rows, 'amount'));
        foreach ($amounts as $amt) {
            $this->assertGreaterThan(-4000.0, $amt, "Amount $amt looks like a leaked sidebar total");
        }

        // Opening balance from summary panel
        $this->assertEqualsWithDelta(10688.17, $parsed['opening'], 0.01);
        $this->assertSame(0, $parsed['unparsed']);
    }

    /**
     * Virgin Money: Rate lines that have sidebar noise appended must still be dropped,
     * and Stg Amt lines that have sidebar noise appended must still yield the correct amount.
     */
    public function test_virgin_rate_and_stg_lines_with_appended_sidebar_noise(): void
    {
        // 5000.00 - 16.91 = 4983.09; 4983.09 - 163.62 = 4819.47
        $page = implode("\n", [
            'Virgin Money',
            'Statement date 05 April 2026',
            'Opening balance 5000.00',
            'Date Description Debits Credits Balance',
            '03 Apr Cls 77 Myr 92.00',
            'Rate 0.18380 Account number 34085729',
            'Stg Amt 16.91 16.91 Sort code 05-04-24',
            '04 Apr DD EE Limited 163.62 4819.47',
            'Closing balance 4819.47',
        ]);

        $parsed = (new VirginMoneyParser())->parseDocument([1 => $page], false);
        $rows   = $this->rows($parsed);

        $this->assertCount(2, $rows);
        $amounts = array_map('floatval', array_column($rows, 'amount'));
        $this->assertEqualsWithDelta(-16.91, $amounts[0], 0.01, 'MYR tx sterling amount');
        $this->assertEqualsWithDelta(-163.62, $amounts[1], 0.01, 'DD amount');
        $this->assertSame(0, $parsed['unparsed']);
    }
}
