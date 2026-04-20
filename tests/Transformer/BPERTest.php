<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\BPER;

class BPERTest extends TestCase
{
    public function test_process(): void
    {
        $transformer = new BPER(__DIR__ . '/../Fixtures/movimenti-bper.xls');
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

    public function test_canHandle_returns_true_for_valid_bper_file(): void
    {
        $this->assertTrue(BPER::canHandle(__DIR__ . '/../Fixtures/movimenti-bper.xls'));
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
