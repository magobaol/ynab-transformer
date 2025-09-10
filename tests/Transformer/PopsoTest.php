<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\Popso;

class PopsoTest extends TestCase
{
    public function test_should_skip_rows_with_balances()
    {
        $transformer = new Popso(__DIR__.'/../Fixtures/movimenti-popso-solo-saldi.csv');

        $transactions = $transformer->transformToYNAB();

        $this->assertCount(0, $transactions->toArray());
    }

    public function test_transform()
    {
        $transformer = new Popso(__DIR__.'/../Fixtures/movimenti-popso.csv');

        $transactions = $transformer->transformToYNAB();

        $this->assertCount(6, $transactions->toArray());

        $transaction1 = [
            'date' => '2021-07-30',
            'payee' => 'SDD 456879',
            'memo' => '',
            'outflow' => '60.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2021-07-30',
            'payee' => 'Addebito Preautorizzato SDD N. 456879  ',
            'memo' => '',
            'outflow' => '1.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

        $transaction3 = [
            'date' => '2021-08-02',
            'payee' => 'SDD 456872',
            'memo' => '',
            'outflow' => '289.45',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction3, $transactions->getByIndex(2)->toArray());

        $transaction4 = [
            'date' => '2021-08-02',
            'payee' => 'Addebito Preautorizzato SDD N. 456872  ',
            'memo' => '',
            'outflow' => '1.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction4, $transactions->getByIndex(3)->toArray());

        $transaction5 = [
            'date' => '2021-08-03',
            'payee' => 'TRIBUTI',
            'memo' => '',
            'outflow' => '10.38',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction5, $transactions->getByIndex(4)->toArray());

        $transaction6 = [
            'date' => '2021-08-13',
            'payee' => 'COMPANY NAME  ',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '1000.50'
        ];
        $this->assertEquals($transaction6, $transactions->getByIndex(5)->toArray());
    }

    public function test_canHandle_returns_true_for_valid_popso_file()
    {
        $canHandle = Popso::canHandle(__DIR__ . '/../Fixtures/movimenti-popso.csv');
        $this->assertTrue($canHandle);
    }

    public function test_canHandle_returns_true_for_valid_popso_solo_saldi_file()
    {
        $canHandle = Popso::canHandle(__DIR__ . '/../Fixtures/movimenti-popso-solo-saldi.csv');
        $this->assertTrue($canHandle);
    }

    public function test_canHandle_returns_false_for_non_popso_file()
    {
        $canHandle = Popso::canHandle(__DIR__ . '/../Fixtures/movimenti-fineco.xlsx');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_different_csv_file()
    {
        $canHandle = Popso::canHandle(__DIR__ . '/../Fixtures/movimenti-revolut.csv');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_nonexistent_file()
    {
        $canHandle = Popso::canHandle(__DIR__ . '/../Fixtures/nonexistent.csv');
        $this->assertFalse($canHandle);
    }

}