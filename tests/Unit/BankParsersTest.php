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

    /**
     * Real-world Virgin Money Club Account Statement No: 299 scenario with:
     * - Interleaved 2-column OCR merging sidebar FSCS notices into Rate and Stg lines
     * - Currency tokens glued to card numbers without spaces (Cls 77Myr) or with trailing artifacts (77Myr_)
     * - Prefix OCR noise (nt Cls, Q Cls, a Cls)
     * - Rate line carrying the Sterling Amount and/or running balance
     * - Domestic card payment (Card 77 Expedia) that must NOT be misclassified as FX
     * - 0 unparsed rows, exact balance reconciliation across all pages.
     */
    public function test_virgin_statement_299_with_merged_ocr_lines_and_sidebar_noise(): void
    {
        $pages = [
            1 => implode("\n", [
                'Virgin Money',
                'Statement date 30 January 2026',
                'Date Description Debits Credits Balance',
                '31 Dec 2025 Previous statement 10688.17',
                '02 Jan Cls 77Myr 12.00',
                'Rate 0.18416',
                'Stg Amt 2.21 2.21',
                'Cls 77Myr_ 12.00',
                'Deposits ae nea in this account are eligible . Rate 0.18416 for cover under the Financial 2.21 Services Compensation Scheme',
                'Cls 77 Myr 17.20 (FSCS) 10,688.17',
                'Rate 0.18372',
                'Stg Amt 3.16 3.16',
                'Cls 77 Myr 127.20 Debits Rate 0.18380 £3,736.20',
                'Stg Amt 23.38 23.38 10657.21 Credits £0.85',
                '05 Jan Cls 77 Myr 12.00 Rat are 0.18416 $6952.82 3',
                'Stg Amt 2.21 2.21',
                'a Cls 77Myr 58.80 SO=StandingOrder a = ba §',
                'Rate 0.18452',
                'Stg Amt 10.85 10.85',
                'Q Cls 77 Myr 92.00 Cls = Contactless Debit Card Transaction',
                'Rate 0.18380',
                'Stg Amt 16.91 16.91 10627.24',
            ]),
            2 => implode("\n", [
                'Virgin Money',
                'Statement date 30 January 2026',
                'Balance brought forward from previous page 10627.24',
                '05 Jan Cls 77 Myr 132.60 MR T J BROOK',
                'Rate 0.18386',
                'Stg Amt 24.38 24.38',
                'Cls 77 Myr 149.00 05-04-24 S4085729 27.39 10575.47',
                'DD Ee Limited 163.62 10411.85',
                '07 Jan Cls 77 Myr 12.00',
                'Rate 0.18416',
                'Stg Amt 2.21 2.21',
                'Cls 77 Myr 111.30 eligible for cover under the Rate 0.18382 Financial Services Compensation 20.46 10389.18 Scheme (FSCS)',
                '08 Jan Cls 77 Myr 20.00',
                'Rate 0.18450',
                'Stg Amt 3.69 3.69',
                'Cls 77 Myr 29.70 Rate 0.18451 £3736.20',
                'Stg Amt 5.48 5.48 £0.85',
                'Cls 77 Myr 95.40',
                'Rate 0.18438',
                'Stg Amt 17.59 17.59 10362.42',
                '09 Jan Cls 77 Myr 12.00',
                'Rate 0.18333',
                'Stg Amt 2.20 2.20',
                'Cls 77 Myr 53.70',
                'Rate 0.18361',
                'Stg Amt 9.86 9.86',
                'DD Postcode Lottery 12.25 10338.11',
                '12 Jan Cls 77 Myr 12.00',
                'Rate 0.18333',
                'Stg Amt 2.20 2.20',
                'Cls 77 Myr 12.00',
                'Rate 0.18416',
                'Stg Amt 2.21 2.21',
                'Cls 77 Myr 13.00',
                'Rate 0.18384',
                'Stg Amt 2.39 2.39',
                'Cls 77 Myr 15.00',
                'Rate 0.18400',
                'Stg Amt 2.76 2.76',
                'Cls 77 Myr 15.40',
                'Rate 0.18376',
                'Stg Amt 2.83 2.83',
                'Cls 77 Myr 15.90',
                'Rate 0.18427',
                'Stg Amt 2.93 2.93',
            ]),
            3 => implode("\n", [
                'Virgin Money',
                'Statement date 30 January 2026',
                'Balance brought forward from previous page 10322.79',
                '12 Jan nt Cls 77 Myr 26.50 MR T J BROOK',
                'Rate 0.18415',
                'Stg Amt 4.88 4.88',
                'Cls 77 Myr 40.00 05-04-24 sAoas728',
                'Rate 0.18425',
                'Stg Amt 7.37 7.37',
                'Cls 77 Myr 53.10 StgAmt g 9.75 :',
                'Cls 77 Myr 84.25 Deposits - in this ais account are Rate 0.18421 eligible for cover under the 15.52 Financial Services Compensation',
                'Cls 77 Myr 138.00 Scheme (FSCS) Reie G1b420 25.42 10259.85 $10688.17',
                '13 Jan Cls 77 Myr 12.00',
                'Rate 0.18416',
                'Stg Amt 2.21 2.21',
                'Cls 77 Myr 15.40',
                'Rate 0.18441',
                'Stg Amt 2.84 2.84',
                'Cls 77Myr 26.40 -Direat Debit DD Rate 0.18409 SO -Standing Order',
                'Stg Amt 4.86 4.86',
                'Cls 77 Myr 91.00 waa beeneronelag ne Rate 0.18417 Cls = Contactless Debit Card Transaction',
                'Stg Amt 16.76 16.76',
                'Card 77 Expedia 73347 London 286.05 9947.13',
                '14 Jan Cls 77 Myr 12.00 ace eyment',
                'Rate 0.18416',
                'Stg Amt 2.21 2.21',
                'Cls 77 Myr 63.00 Rate 0.18396',
                'Stg Amt 11.59 11.59',
                'Cls 77Myr 95.40',
                'Rate 0.18417',
                'Stg Amt 17.57 17.57',
                'Mb Gillian Vickerm 500.00 9415.76',
                '15 Jan Cls 77 Myr 12.00',
                'Rate 0.18416',
                'Stg Amt 2.21 2.21',
                'Cls 77 Myr 57.00',
                'Rate 0.18385',
                'Stg Amt 10.48 10.48',
                'Cls 77 Myr 63.60',
                'Rate 0.18396',
                'Stg Amt 11.70 11.70',
                'Mb Gillian Vickerm 5.00 9386.37',
            ]),
        ];

        $parsed = (new VirginMoneyParser())->parseDocument($pages, true);
        $rows   = $this->rows($parsed);

        // Exactly 42 transactions across the 3 pages
        $this->assertCount(42, $rows);

        // Zero unparsed rows
        $this->assertSame(0, $parsed['unparsed']);

        // Correct opening and closing balances
        $this->assertEqualsWithDelta(10688.17, $parsed['opening'], 0.01);
        $this->assertEqualsWithDelta(9386.37, $parsed['closing'], 0.01);

        // Verify that Expedia domestic transaction is preserved as 286.05
        $expedia = array_values(array_filter($rows, fn ($r) => str_contains($r['description'], 'Expedia')));
        $this->assertCount(1, $expedia);
        $this->assertEqualsWithDelta(-286.05, (float) $expedia[0]['amount'], 0.01);
        $this->assertEqualsWithDelta(9947.13, (float) $expedia[0]['balance_after'], 0.01);

        // Verify that leaked sidebar amounts never appear as transaction amounts
        $amounts = array_map('floatval', array_column($rows, 'amount'));
        foreach ($amounts as $amt) {
            $this->assertGreaterThan(-1000.0, $amt, "Amount $amt looks like a leaked sidebar balance or fake debit");
        }
    }

    /**
     * OCR loses the comma/decimal point of the printed running balance and glues the next
     * row's day onto the same line: "Mb Gillian Vickerm 453.00 1462551 04". The amount must
     * stay on its own row instead of surfacing later against the next transaction's amount.
     */
    public function test_virgin_amount_survives_garbled_balance_and_trailing_day(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Your M Plus Account statement',
            'Statement No: 280',
            'Statement date',
            '01 May 2025',
            'Account number',
            '34605723',
            'Date Description Debits Credits Balance',
            'Previous statement 15078.51',
            '02 Apr Mb Cash 77 Bmachuddesri 200.00 14878.51',
            'Mb Gillian Vickerm 453.00 1462551 04',
            'Aor',
            'Caecilian Ltd',
            'Deposits in this account are',
            'Co in this account are',
            'DD Postcode Lottery 12.25 14413.26',
            'Deposits in this account are eligible',
            'for cover under the Financial',
            'Services Compensation Scheme',
            '(FSCS)',
            'Opening balance',
            '15078.51',
            'Debits',
            '1454.36',
            'Credits',
            '0.00',
            'Closing balance',
            '14413.26',
        ])], true);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(0, $parsed['unparsed']);

        $this->assertEqualsWithDelta(-200.00, (float) $rows[0]['amount'], 0.01);
        // The 453.00 debit keeps its own row, not the next row's 12.25.
        $this->assertEqualsWithDelta(-453.00, (float) $rows[1]['amount'], 0.01);
        $this->assertStringContainsString('Vickerm', $rows[1]['description']);
        $this->assertStringNotContainsString('Postcode', $rows[1]['description']);
        $this->assertEqualsWithDelta(-12.25, (float) $rows[2]['amount'], 0.01);
        $this->assertStringContainsString('Postcode Lottery', $rows[2]['description']);
        $this->assertStringNotContainsString('Vickerm', $rows[2]['description']);

        $this->assertEqualsWithDelta(15078.51, (float) $parsed['opening'], 0.01);
        $this->assertEqualsWithDelta(14413.26, (float) $parsed['closing'], 0.01);
    }

    /**
     * FX rows print "Stg Amt 23.38" in the description column, repeat 23.38 in the
     * Debits column, and put the day's running balance in the last column. OCR merges
     * that into "Stg Amt 23.38 23.38 1065721" (balance loses its decimal point) and
     * adds debris before the label on the next row ("3 Stg Amt 2.21 2.21").
     */
    public function test_virgin_fx_rows_keep_their_printed_balance(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 30 January 2026',
            'Date Description Debits Credits Balance',
            '31 Dec 2025 Previousstatement 10688.17',
            '02 Jan Cls 77 Myr 12.00',
            'Rate 0.18416',
            'StgAmt 2.21 2.21',
            'Cls 77 Myr 12.00',
            'Rate 0.18416',
            'StgAmt 2.21 2.21',
            'Cls 77 Myr 17.20',
            'Rate 0.18372',
            'StgAmt 3.16 3.16',
            'Cls 77 Myr 127.20',
            'Rate 0.18380',
            'StgAmt 23.38 23.38 1065721',
            '05 Jan Cls 77 Myr 12.00',
            'Rate 0.18416',
            '3 StgAmt 2.21 2.21',
            'a Cls 77Myr 58.80',
            'Rate 0.18452',
            '.! StgAmt 10.85 10.85',
            '@ Cls 77 Myr 92.00',
            'Rate 0.18380',
            'Stg Amt 16.91 16.91',
            'Closing balance 10627.24',
        ])], true);

        $rows = $this->rows($parsed);
        $this->assertSame(0, $parsed['unparsed']);
        $this->assertCount(7, $rows);
        $this->assertSame(
            ['2026-01-02', '2026-01-02', '2026-01-02', '2026-01-02', '2026-01-05', '2026-01-05', '2026-01-05'],
            array_column($rows, 'transaction_date')
        );
        $this->assertEqualsWithDelta(-23.38, (float) $rows[3]['amount'], 0.01);
        // 1065721 is the printed balance 10,657.21 with the decimal point lost.
        $this->assertEqualsWithDelta(10657.21, (float) $rows[3]['balance_after'], 0.01);
        $this->assertStringNotContainsString('1065721', $rows[3]['description']);
        // The next row's duplicated 2.21 is the Debits column, not a PDF balance.
        $this->assertEqualsWithDelta(-2.21, (float) $rows[4]['amount'], 0.01);
        $this->assertNull($rows[4]['balance_after']);
        // OCR debris before the label must not turn the foreign amount (58.80 / 92.00)
        // into the transaction amount - the sterling values are 10.85 and 16.91.
        $this->assertEqualsWithDelta(-10.85, (float) $rows[5]['amount'], 0.01);
        $this->assertEqualsWithDelta(-16.91, (float) $rows[6]['amount'], 0.01);
        $this->assertNull($rows[6]['balance_after']);
        $this->assertEqualsWithDelta(10627.24, (float) $parsed['closing'], 0.01);
        $this->assertStringNotContainsString('Stg', implode(' ', array_column($rows, 'description')));
    }

    /** Newer layouts print the calendar year on every row; those rows used to be dropped. */
    public function test_virgin_rows_that_print_a_full_year_are_extracted(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Opening balance 1,000.00',
            'Date Description Debits Credits Balance',
            '03 Apr 2025 TESCO STORES 12.34 987.66',
            'SHELL 40.00 947.66',
            '04 Apr 2025 SALARY 2,000.00 2,947.66',
            'Closing balance 2,947.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['2025-04-03', '2025-04-03', '2025-04-04'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** Numeric day-first dates (03/04/2025) are accepted as transaction rows. */
    public function test_virgin_numeric_dates_are_extracted(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Opening balance 1,000.00',
            'Date Description Money out Money in Balance',
            '03/04/2025 TESCO STORES 12.34 987.66',
            'SHELL 40.00 947.66',
            '04/04/2025 SALARY 2,000.00 2,947.66',
            'Closing balance 2,947.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['2025-04-03', '2025-04-03', '2025-04-04'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    /**
     * The same payee repeated on one day, each row wrapped over two lines, with the
     * printed running balance only on a few rows (OCR of this layout usually loses it).
     * Every row must survive - a repeated "Cls 24 The Lockwood / Huddersfield" block
     * must not swallow one of its amounts.
     */
    public function test_virgin_repeated_payee_rows_on_the_same_day_are_all_kept(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Your Club M Plus Account statement',
            'Statement date',
            '30 September 2023',
            'Date Description Debits Credits Balance',
            '1Sep Balance brought forward from previous page',
            'Cls 24 Ppoint-*Close',
            'Huddersfield 22.05 28756.84',
            '3 Sep Cls 24 Costcutter',
            'Harrogate 11.99 28744.85',
            '3 Sep Cls 24 The Lockwood',
            'Huddersfield 5.70',
            'Cls 24 The Lockwood',
            'Huddersfield 6.50',
            'Cls 24 The Lockwood',
            'Huddersfield 10.20',
            'Cls 24 The Lockwood',
            'Huddersfield 10.50',
            '7 Sep DD Shelter Nat Camp 15.00',
            '8 Sep Cls 24 Co-Op Group F 104213 25.45',
            '9 Sep Cls 24 Ppoint-*Close 27.97',
            'DD British Gas 51.28',
            'DD British Gas 103.56 28488.69',
            'Cash 24 Postharrrogate 200.00',
        ])], true);

        $rows = $this->rows($parsed);
        $this->assertCount(12, $rows);
        $this->assertEqualsWithDelta(
            [-22.05, -11.99, -5.70, -6.50, -10.20, -10.50, -15.00, -25.45, -27.97, -51.28, -103.56, -200.00],
            array_map('floatval', array_column($rows, 'amount')),
            0.01
        );
        // The three printed balances stay with the rows that print them.
        $this->assertEqualsWithDelta(28756.84, (float) $rows[0]['balance_after'], 0.01);
        $this->assertEqualsWithDelta(28744.85, (float) $rows[1]['balance_after'], 0.01);
        $this->assertEqualsWithDelta(28488.69, (float) $rows[10]['balance_after'], 0.01);
    }

    /** A clipped OCR balance must not be saved as the row's PDF balance. */
    public function test_virgin_discards_an_implausibly_truncated_running_balance(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 30 June 2025',
            'Opening balance 16,139.31',
            'Date Description Debits Credits Balance',
            '24 Jun DD Wateraid 12.00 16127.31',
            '25 Jun Cls 77 Sainsburys S/Huddersfield 15.55 16.76',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(2, $rows);
        $this->assertEqualsWithDelta(-15.55, (float) $rows[1]['amount'], 0.01);
        $this->assertNull($rows[1]['balance_after']);
    }

    /**
     * A scan glues punctuation onto a printed amount ("Huddersfield 10.50,").
     * The token is then not money, the row is read as description text and disappears
     * (its amount also lands on the next row). The amount must still be read.
     */
    public function test_virgin_amount_with_scan_punctuation_keeps_its_row(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 30 September 2023',
            'Date Description Debits Credits Balance',
            '11Sep Balance brought forward from previous page',
            'Cls 24 Ppoint-*Close',
            'Huddersfield 22.05 28756.84',
            '13 Sep Cls 24 Costcutter',
            'Harrogate 11.99 28744.85',
            '18 Sep Cls 24 The Lockwood',
            'Huddersfield 5.70',
            'Cls 24 The Lockwood',
            'Huddersfield 6.50,',
            'Cls 24 The Lockwood',
            'Huddersfield 10.20.',
            'Cls 24 The Lockwood',
            'Huddersfield 10.50',
            'DD Shelter Nat Camp 15.00',
            'Cls 24 Co-Op Group F 104213 25.45',
            'Cls 24 Ppoint-*Close 27.97',
            'DD British Gas 51.28',
            'DD British Gas 103.56 28488.69',
        ])], true);

        $rows = $this->rows($parsed);
        $this->assertCount(11, $rows);
        $this->assertEqualsWithDelta(
            [-22.05, -11.99, -5.70, -6.50, -10.20, -10.50, -15.00, -25.45, -27.97, -51.28, -103.56],
            array_map('floatval', array_column($rows, 'amount')),
            0.01
        );
        $this->assertSame(0, $parsed['unparsed']);
        // The 10.50 line must not leak into the next payee's description.
        $this->assertStringContainsString('Shelter', $rows[6]['description']);
        $this->assertStringNotContainsString('10.50', $rows[6]['description']);
    }

    /**
     * A transaction whose text was lost but whose printed amount closes the balance gap is
     * recovered from that line instead of vanishing (this is what "missing rows" looked like).
     */
    public function test_virgin_recovers_a_row_from_the_printed_balance_gap(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 05 April 2025',
            'Opening balance 1,000.00',
            'Date Description Debits Credits Balance',
            '03 Apr TESCO STORES 12.34 987.66',
            '04 Apr 453.00 534.66',
            '05 Apr SHELL 40.00 494.66',
            'Closing balance 494.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(3, $rows);
        $this->assertSame(['2025-04-03', '2025-04-04', '2025-04-05'], array_column($rows, 'transaction_date'));
        $this->assertEqualsWithDelta(-453.00, (float) $rows[1]['amount'], 0.01);
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** Promo/footer text in the middle of a page must not drop the rows printed after it. */
    public function test_virgin_resumes_after_footer_text_on_the_same_page(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 05 April 2025',
            'Opening balance 1,000.00',
            'Date Description Debits Credits Balance',
            '03 Apr TESCO STORES 12.34 987.66',
            'Just so you know, your rate changes soon',
            '04 Apr SHELL 40.00 947.66',
            'Closing balance 947.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(2, $rows);
        $this->assertSame(['2025-04-03', '2025-04-04'], array_column($rows, 'transaction_date'));
        $this->assertSame(0, $parsed['unparsed']);
    }

    /** A dated line whose text is the column header is not a transaction. */
    public function test_virgin_dated_header_line_is_not_a_row(): void
    {
        $parsed = (new VirginMoneyParser())->parseDocument([1 => implode("\n", [
            'Virgin Money',
            'Statement date 05 April 2025',
            'Opening balance 1,000.00',
            '03 Apr 2025 Date Description Debits Credits Balance',
            '03 Apr 2025 TESCO STORES 12.34 987.66',
            'Closing balance 987.66',
        ])], false);

        $rows = $this->rows($parsed);
        $this->assertCount(1, $rows);
        $this->assertSame('TESCO STORES', $rows[0]['description']);
    }

    /** A day with more rows than the subset-sum limit is still signed against its balance. */
    public function test_balance_resolver_handles_a_day_longer_than_the_group_limit(): void
    {
        $rows = [];
        for ($i = 0; $i < 34; $i++) {
            $rows[] = ['abs' => 10.0, 'balance' => null, 'hint' => -1, 'key' => 'p' . $i];
        }
        // One credit among 34 debits: hints alone cannot reach the printed balance.
        $rows[] = ['abs' => 500.0, 'balance' => null, 'hint' => 0, 'key' => 'salary'];
        $rows[] = ['abs' => 0.0, 'balance' => 1160.0, 'hint' => 0, 'key' => 'end'];

        $res = \App\Services\BankParsers\BalanceResolver::resolve($rows, 1000.0, 1160.0);

        $this->assertSame(0, $res['unresolved']);
        $this->assertEqualsWithDelta(1, $rows[count($rows) - 2]['sign'], 0.001);
        $this->assertEqualsWithDelta(-1, $rows[0]['sign'], 0.001);
    }
}
