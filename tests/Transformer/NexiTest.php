<?php

namespace Tests\Transformer;

use Transformer\Nexi;
use PHPUnit\Framework\TestCase;

class NexiTest extends TestCase
{
    public function test_process()
    {
        $nexiToYNAB = new Nexi(__DIR__.'/../Fixtures/movimenti-nexi.xlsx');
        $YNABTransactions = $nexiToYNAB->transformToYNAB();

        $transactions = $YNABTransactions;

        $this->assertCount(2, $transactions->toArray());

        $transaction1 = [
            'date' => '2021-08-03',
            'payee' => 'AMZ*Amazon.it',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '53.25'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2021-08-03',
            'payee' => 'Subscription PRO',
            'memo' => '',
            'outflow' => '1.71',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

    }

    public function test_canHandle_returns_true_for_valid_nexi_file()
    {
        $canHandle = Nexi::canHandle(__DIR__ . '/../Fixtures/movimenti-nexi.xlsx');
        $this->assertTrue($canHandle);
    }

    public function test_canHandle_returns_false_for_non_nexi_file()
    {
        $canHandle = Nexi::canHandle(__DIR__ . '/../Fixtures/movimenti-fineco.xlsx');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_csv_file()
    {
        $canHandle = Nexi::canHandle(__DIR__ . '/../Fixtures/movimenti-revolut.csv');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_nonexistent_file()
    {
        $canHandle = Nexi::canHandle(__DIR__ . '/../Fixtures/nonexistent.xlsx');
        $this->assertFalse($canHandle);
    }

}