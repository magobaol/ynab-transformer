<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\Fineco;

class FinecoTest extends TestCase
{

    public function test_transform()
    {
        $transformer = new Fineco(__DIR__.'/../Fixtures/movimenti-fineco.xlsx');
        $transactions = $transformer->transformToYNAB();
        $this->assertCount(6, $transactions->toArray());

        $transaction1 = [
            'date' => '2021-08-21',
            'payee' => 'AMZN Mktp IT',
            'memo' => '',
            'outflow' => '11.59',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2021-08-21',
            'payee' => '',
            'memo' => '',
            'outflow' => '18.10',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

        $transaction3 = [
            'date' => '2021-08-21',
            'payee' => 'Pag. del 20/08/21 ora 16:59 presso: pasticceria',
            'memo' => '(20/08/2021) ',
            'outflow' => '63.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction3, $transactions->getByIndex(2)->toArray());

        $transaction4 = [
            'date' => '2021-08-11',
            'payee' => 'Ord: Company name Dt-ord: 11/08/2021',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '1000.00'
        ];
        $this->assertEquals($transaction4, $transactions->getByIndex(3)->toArray());

        $transaction5 = [
            'date' => '2021-08-11',
            'payee' => 'Ord: Company name Dt-ord: 10/08/2021',
            'memo' => '(10/08/2021) ',
            'outflow' => '0.00',
            'inflow' => '500.00'
        ];
        $this->assertEquals($transaction5, $transactions->getByIndex(4)->toArray());

        $transaction6 = [
            'date' => '2021-08-17',
            'payee' => 'Prelevamento carta N° *****        651  Data operazione 16/8/2021',
            'memo' => '(16/08/2021) Prelievo',
            'outflow' => '200.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction6, $transactions->getByIndex(5)->toArray());

    }

    public function test_transform_should_skip_rows_with_status_not_contabilizzato()
    {
        $transformer = new Fineco(__DIR__ . '/../Fixtures/movimenti-fineco-test-contabilizzati.xlsx');
        $transactions = $transformer->transformToYNAB();
        $this->assertCount(1, $transactions->toArray());

        $transaction1 = [
            'date' => '2021-08-31',
            'payee' => 'AMZN Mktp IT',
            'memo' => '',
            'outflow' => '15.56',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

    }

    }