<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\Popso;
use Transformer\Revolut;

class RevolutTest extends TestCase
{
    public function test_should_skip_rows_with_state_not_completed()
    {
        $transformer = new Revolut(__DIR__.'/../Fixtures/movimenti-revolut-not-completed.csv');

        $transactions = $transformer->transformToYNAB();

        $this->assertCount(0, $transactions->toArray());
    }

    public function test_transform()
    {
        $transformer = new Revolut(__DIR__.'/../Fixtures/movimenti-revolut.csv');

        $transactions = $transformer->transformToYNAB();

        $this->assertCount(5, $transactions->toArray());

        $transaction1 = [
            'date' => '2023-10-01',
            'payee' => 'Top-Up by *1466',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '40.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2023-10-01',
            'payee' => 'To ANDREA FACE',
            'memo' => '',
            'outflow' => '10.20',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

        $transaction3 = [
            'date' => '2023-10-01',
            'payee' => 'To ANDREA FACE',
            'memo' => '',
            'outflow' => '40.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction3, $transactions->getByIndex(2)->toArray());

        $transaction4 = [
            'date' => '2023-10-31',
            'payee' => 'Top-Up by *1466',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '20.00'
        ];
        $this->assertEquals($transaction4, $transactions->getByIndex(3)->toArray());

        $transaction5 = [
            'date' => '2023-10-31',
            'payee' => 'From ANNA MARIA SANTONI',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '20.00'
        ];
        $this->assertEquals($transaction5, $transactions->getByIndex(4)->toArray());

    }
}
