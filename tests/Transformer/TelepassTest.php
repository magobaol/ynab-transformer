<?php

namespace Tests\Transformer;

use Transformer\Nexi;
use PHPUnit\Framework\TestCase;
use Transformer\Telepass;

class TelepassTest extends TestCase
{
    public function test_process()
    {
        $telepassToYNAB = new Telepass(__DIR__.'/../Fixtures/movimenti-telepass.xls');
        $YNABTransactions = $telepassToYNAB->transformToYNAB();

        $transactions = $YNABTransactions;

        $this->assertCount(2, $transactions->toArray());

        $transaction1 = [
            'date' => '2024-01-24',
            'payee' => 'Telepass',
            'memo' => 'BZN /EGA EGGEN  - ROMA EST',
            'outflow' => '50.60',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2024-01-07',
            'payee' => 'Telepass',
            'memo' => 'ROMA NORD       - BZN /EGA EGGEN',
            'outflow' => '48.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

    }
}