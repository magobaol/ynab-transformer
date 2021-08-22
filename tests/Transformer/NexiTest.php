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

        $this->assertCount(3, $transactions->toArray());

        $transaction1 = [
            'date' => '2021-08-13',
            'payee' => 'GRUPPO SDA SRL',
            'memo' => '',
            'outflow' => '33.88',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2021-08-03',
            'payee' => 'AMZ*Amazon.it',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '53.25'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

        $transaction3 = [
            'date' => '2021-08-03',
            'payee' => 'Subscription PRO',
            'memo' => '',
            'outflow' => '1.71',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction3, $transactions->getByIndex(2)->toArray());

    }
}