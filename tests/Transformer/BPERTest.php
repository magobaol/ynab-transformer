<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\BPER;

class BPERTest extends TestCase
{
    public function test_process_with_date_filter_layout(): void
    {
        $transformer = new BPER(__DIR__ . '/../Fixtures/movimenti-bper-with-date-filter.xls');
        $transactions = $transformer->transformToYNAB();

        $this->assertCount(19, $transactions->toArray());

        $first = $transactions->getByIndex(0)->toArray();
        $this->assertSame('2026-04-20', $first['date']);
        $this->assertStringStartsWith('BONIFICO ISTANTANEO o/c: LUCIA BIANCHI', $first['payee']);
        $this->assertSame('', $first['memo']);
        $this->assertSame('0.00', $first['outflow']);
        $this->assertSame('503.00', $first['inflow']);

        $last = $transactions->getByIndex(18)->toArray();
        $this->assertSame('2026-04-01', $last['date']);
        $this->assertStringContainsString('AGOS DUCATO SPA', $last['payee']);
        $this->assertSame('', $last['memo']);
        $this->assertSame('89.05', $last['outflow']);
        $this->assertSame('0.00', $last['inflow']);

        // Row with data valuta different from data operazione -> memo carries the valuta date.
        $imposta = $this->findTransactionContaining($transactions->toArray(), 'IMPOSTA DI BOLLO');
        $this->assertNotNull($imposta);
        $this->assertSame('2026-04-02', $imposta['date']);
        $this->assertSame('(31/03/2026)', $imposta['memo']);
        $this->assertSame('8.55', $imposta['outflow']);

        // Descriptions get whitespace-collapsed.
        $commissione = $this->findTransactionContaining($transactions->toArray(), 'COMM. BON. ISTANTANEO');
        $this->assertNotNull($commissione);
        $this->assertStringNotContainsString('  ', $commissione['payee']);
    }

    public function test_process_skips_non_contabilizzato_rows(): void
    {
        $transformer = new BPER(__DIR__ . '/../Fixtures/movimenti-bper-test-contabilizzati.xls');
        $transactions = $transformer->transformToYNAB();

        $this->assertCount(18, $transactions->toArray());
        foreach ($transactions->toArray() as $tx) {
            $this->assertStringNotContainsString('AGOS DUCATO', $tx['payee']);
        }
    }

    public function test_process_without_date_filter_layout(): void
    {
        $transformer = new BPER(__DIR__ . '/../Fixtures/movimenti-bper-without-date-filter.xls');
        $transactions = $transformer->transformToYNAB()->toArray();

        // 45 rows in the export, one of which is "Da contabilizzare" and gets skipped.
        $this->assertCount(44, $transactions);

        // Row 18 in the file is "Da contabilizzare" (06 maggio 2026) -> must not appear.
        foreach ($transactions as $tx) {
            $this->assertNotSame('2026-05-06', $tx['date']);
        }

        $first = $transactions[0];
        $this->assertSame('2026-05-05', $first['date']);
        $this->assertStringStartsWith('COMM. BON. ISTANTANEO a favore di Lucia Bianchi', $first['payee']);
        $this->assertSame('1.50', $first['outflow']);
        $this->assertSame('0.00', $first['inflow']);
    }

    public function test_canHandle_returns_true_for_with_date_filter_layout(): void
    {
        $this->assertTrue(BPER::canHandle(__DIR__ . '/../Fixtures/movimenti-bper-with-date-filter.xls'));
    }

    public function test_canHandle_returns_true_for_without_date_filter_layout(): void
    {
        $this->assertTrue(BPER::canHandle(__DIR__ . '/../Fixtures/movimenti-bper-without-date-filter.xls'));
    }

    public function test_canHandle_returns_false_for_telepass_file(): void
    {
        $this->assertFalse(BPER::canHandle(__DIR__ . '/../Fixtures/movimenti-telepass.xls'));
    }

    public function test_canHandle_returns_false_for_xlsx_file(): void
    {
        $this->assertFalse(BPER::canHandle(__DIR__ . '/../Fixtures/movimenti-fineco.xlsx'));
    }

    public function test_canHandle_returns_false_for_csv_file(): void
    {
        $this->assertFalse(BPER::canHandle(__DIR__ . '/../Fixtures/movimenti-revolut.csv'));
    }

    public function test_canHandle_returns_false_for_nonexistent_file(): void
    {
        $this->assertFalse(BPER::canHandle(__DIR__ . '/../Fixtures/nonexistent.xls'));
    }

    private function findTransactionContaining(array $transactions, string $needle): ?array
    {
        foreach ($transactions as $tx) {
            if (str_contains($tx['payee'], $needle)) {
                return $tx;
            }
        }
        return null;
    }
}
