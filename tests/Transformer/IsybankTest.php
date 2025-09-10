<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\Isybank;
use Transformer\Poste;

class IsybankTest extends TestCase
{

    public function test_transform()
    {
        $transformer = new Isybank(__DIR__.'/../Fixtures/movimenti-isybank.xlsx');
        $transactions = $transformer->transformToYNAB();
        $this->assertCount(4, $transactions->toArray());

        $transaction1 = [
            'date' => '2025-07-25',
            'payee' => 'Addebito diretto disposto a favore di FASTWEB SPA MANDATO 3F3811A20244776',
            'memo' => '',
            'outflow' => '28.95',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2025-07-22',
            'payee' => 'D\'ada Srl Via Prenestina 38',
            'memo' => '',
            'outflow' => '10.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

        $transaction3 = [
            'date' => '2025-07-20',
            'payee' => 'Le Delizie Di Zio Bibi Via',
            'memo' => '',
            'outflow' => '40.40',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction3, $transactions->getByIndex(2)->toArray());

        $transaction4 = [
            'date' => '2025-07-18',
            'payee' => 'Ferretti Matteo Via Frascat',
            'memo' => '',
            'outflow' => '9.50',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction4, $transactions->getByIndex(3)->toArray());

    }

    public function test_transform_should_skip_rows_with_status_not_contabilizzato()
    {
        $transformer = new Isybank(__DIR__ . '/../Fixtures/movimenti-isybank-test-contabilizzati.xlsx');
        $transactions = $transformer->transformToYNAB();
        $this->assertCount(1, $transactions->toArray());

        $transaction1 = [
            'date' => '2025-07-24',
            'payee' => 'Street Of Crocodiles',
            'memo' => '',
            'outflow' => '20.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

    }

    public function test_canHandle_returns_true_for_valid_isybank_file()
    {
        $canHandle = Isybank::canHandle(__DIR__ . '/../Fixtures/movimenti-isybank.xlsx');
        $this->assertTrue($canHandle);
    }

    public function test_canHandle_returns_true_for_valid_isybank_test_file()
    {
        $canHandle = Isybank::canHandle(__DIR__ . '/../Fixtures/movimenti-isybank-test-contabilizzati.xlsx');
        $this->assertTrue($canHandle);
    }

    public function test_canHandle_returns_false_for_non_isybank_file()
    {
        $canHandle = Isybank::canHandle(__DIR__ . '/../Fixtures/movimenti-fineco.xlsx');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_csv_file()
    {
        $canHandle = Isybank::canHandle(__DIR__ . '/../Fixtures/movimenti-revolut.csv');
        $this->assertFalse($canHandle);
    }

    public function test_canHandle_returns_false_for_nonexistent_file()
    {
        $canHandle = Isybank::canHandle(__DIR__ . '/../Fixtures/nonexistent.xlsx');
        $this->assertFalse($canHandle);
    }

}