<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\Poste;

class PosteTest extends TestCase
{

    public function test_transform()
    {
        $transformer = new Poste(__DIR__.'/../Fixtures/movimenti-poste.xlsx');
        $transactions = $transformer->transformToYNAB();
        $this->assertCount(6, $transactions->toArray());

        $transaction1 = [
            'date' => '2025-07-22',
            'payee' => 'PAGAMENTO POS ATAC TAP&GO            22/07/2025 17.49 ROMA          Op.651505 carta ****1507',
            'memo' => '',
            'outflow' => '3.00',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction1, $transactions->getByIndex(0)->toArray());

        $transaction2 = [
            'date' => '2025-07-24',
            'payee' => 'UTILIZZO SCOPERTO RESIDUO FIDO DISPONIBILE EURO                    5,53',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '3.59'
        ];
        $this->assertEquals($transaction2, $transactions->getByIndex(1)->toArray());

        $transaction3 = [
            'date' => '2025-07-24',
            'payee' => 'DOMICILIAZIONE (ADDEBITO DIRETTO SEPA) PayPal Europe S.a.r. CID.LU96ZZZ0000000000000000058          MAN.4V8J224NQSMT6',
            'memo' => '',
            'outflow' => '3.59',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction3, $transactions->getByIndex(2)->toArray());

        $transaction4 = [
            'date' => '2025-07-23',
            'payee' => 'UTILIZZO SCOPERTO RESIDUO FIDO DISPONIBILE EURO                    9,12',
            'memo' => '',
            'outflow' => '0.00',
            'inflow' => '37.90'
        ];
        $this->assertEquals($transaction4, $transactions->getByIndex(3)->toArray());

        $transaction5 = [
            'date' => '2025-07-21',
            'payee' => 'PAGAMENTO POS FARMACIA CAPECCI       21/07/2025 10.02 ROMA          Op.656477 carta ****1507',
            'memo' => '',
            'outflow' => '14.30',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction5, $transactions->getByIndex(4)->toArray());

        $transaction6 = [
            'date' => '2025-07-21',
            'payee' => 'PAGAMENTO POS CAFFE\' CARRA           21/07/2025 10.05 ROMA          Op.655399 carta ****1507',
            'memo' => '',
            'outflow' => '8.50',
            'inflow' => '0.00'
        ];
        $this->assertEquals($transaction6, $transactions->getByIndex(5)->toArray());

    }

}