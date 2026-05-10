<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\Intesa;

class IntesaTest extends TestCase
{
    public function test_process_with_fixture(): void
    {
        $transformer = new Intesa(__DIR__ . '/../Fixtures/movimenti-intesa.xlsx');
        $transactions = $transformer->transformToYNAB()->toArray();

        $this->assertCount(3, $transactions);

        $this->assertSame([
            'date' => '2026-05-04',
            'payee' => 'Pag.finanziamento Rateale',
            'memo' => '',
            'outflow' => '670.15',
            'inflow' => '0.00',
        ], $transactions[0]);

        $this->assertSame([
            'date' => '2026-04-30',
            'payee' => 'Canone Mensile Totale "la Mia Scelta"',
            'memo' => '',
            'outflow' => '7.00',
            'inflow' => '0.00',
        ], $transactions[1]);

        $this->assertSame([
            'date' => '2026-04-30',
            'payee' => 'Bonifico Disposto Da ROSSI MARIO',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '666.15',
        ], $transactions[2]);
    }

    public function test_process_skips_non_contabilizzato_rows(): void
    {
        $transformer = new Intesa(__DIR__ . '/../Fixtures/movimenti-intesa-with-pending.xlsx');
        $transactions = $transformer->transformToYNAB()->toArray();

        // The fixture has 4 rows: 3 CONTABILIZZATO + 1 NON CONTABILIZZATO (500.00 inflow).
        $this->assertCount(3, $transactions);

        // The skipped row is the only one with a 500.00 inflow.
        foreach ($transactions as $tx) {
            $this->assertNotSame('500.00', $tx['inflow']);
        }
    }

    public function test_canHandle_returns_true_for_intesa_xlsx(): void
    {
        $this->assertTrue(Intesa::canHandle(__DIR__ . '/../Fixtures/movimenti-intesa.xlsx'));
    }

    public function test_canHandle_returns_true_for_intesa_with_pending(): void
    {
        $this->assertTrue(Intesa::canHandle(__DIR__ . '/../Fixtures/movimenti-intesa-with-pending.xlsx'));
    }

    public function test_canHandle_returns_false_for_bper_file(): void
    {
        $this->assertFalse(Intesa::canHandle(__DIR__ . '/../Fixtures/movimenti-bper-with-date-filter.xls'));
    }

    public function test_canHandle_returns_false_for_fineco_xlsx(): void
    {
        $this->assertFalse(Intesa::canHandle(__DIR__ . '/../Fixtures/movimenti-fineco.xlsx'));
    }

    public function test_canHandle_returns_false_for_csv_file(): void
    {
        $this->assertFalse(Intesa::canHandle(__DIR__ . '/../Fixtures/movimenti-revolut.csv'));
    }

    public function test_canHandle_returns_false_for_nonexistent_file(): void
    {
        $this->assertFalse(Intesa::canHandle(__DIR__ . '/../Fixtures/nonexistent.xlsx'));
    }
}
