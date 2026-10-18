<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\Satispay;

class SatispayTest extends TestCase
{

    public function test_transform()
    {
        $transformer = new Satispay(__DIR__ . '/../Fixtures/movimenti-satispay.xlsx');
        $transactions = $transformer->transformToYNAB();
        $this->assertCount(3, $transactions->toArray());

        $transaction1 = [
            'date' => '2026-10-04',
            'payee' => 'Lidl',
            'memo' => '',
            'outflow' => '39.81',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2026-10-01',
            'payee' => 'Satispay Transaction Fee',
            'memo' => '',
            'outflow' => '1.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

        $transaction3 = [
            'date' => '2026-10-01',
            'payee' => 'Ricarica Satispay',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '11.00'
        ];
        $this->assertEquals($transaction3, $transactions->getByIndex(2)->toArray());
    }

    public function test_transform_should_skip_rows_with_status_not_approvato()
    {
        $transformer = new Satispay(__DIR__ . '/../Fixtures/movimenti-satispay-test-approvati.xlsx');
        $transactions = $transformer->transformToYNAB();
        $this->assertCount(3, $transactions->toArray());

        $transaction1 = [
            'date' => '2026-10-04',
            'payee' => 'Lidl',
            'memo' => '',
            'outflow' => '39.81',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());
    }

    public function test_canHandle_returns_true_for_valid_satispay_file()
    {
        $canHandle = Satispay::canHandle(__DIR__ . '/../Fixtures/movimenti-satispay.xlsx');
        $this->assertTrue($canHandle);
    }

    public function test_canHandle_returns_false_for_non_satispay_file()
    {
        $canHandle = Satispay::canHandle(__DIR__ . '/../Fixtures/movimenti-isybank.xlsx');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_csv_file()
    {
        $canHandle = Satispay::canHandle(__DIR__ . '/../Fixtures/movimenti-revolut.csv');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_nonexistent_file()
    {
        $canHandle = Satispay::canHandle(__DIR__ . '/../Fixtures/nonexistent.xlsx');
        $this->assertFalse($canHandle);
    }

}
