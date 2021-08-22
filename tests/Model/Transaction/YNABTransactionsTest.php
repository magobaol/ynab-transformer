<?php

namespace Tests\Model\Transaction;

use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PHPUnit\Framework\TestCase;

class YNABTransactionsTest extends TestCase
{
    public function test_toCSV()
    {
        $transactions = new YNABTransactions();
        $transactions->add(
            YNABTransaction::fromStrings('2021-08-21', 'Payee 1', 'Memo 1', '28.98', '')
        );
        $transactions->add(
            YNABTransaction::fromStrings('2021-08-22', 'Payee 2', 'Memo 2', '', '23.12')
        );

        $csv = $transactions->toCSV();

        $expectedCSV = <<<EOL
Date;Payee;Memo;Outflow;Inflow
2021-08-21;Payee 1;Memo 1;28.98;0.00
2021-08-22;Payee 2;Memo 2;0.00;23.12

EOL;

        $this->assertEquals($expectedCSV, $csv);
    }
}