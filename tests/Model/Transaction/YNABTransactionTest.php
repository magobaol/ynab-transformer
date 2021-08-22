<?php

namespace Tests\Model\Transaction;

use Model\Transaction\YNABTransaction;
use PHPUnit\Framework\TestCase;

class YNABTransactionTest extends TestCase
{
    public function test_createFromString()
    {
        $ynab = YNABTransaction::fromStrings('2021-08-12', 'payee', 'memo', '123.45', '987.65');

        $this->assertEquals('2021-08-12', $ynab->getDate()->format('Y-m-d'));
        $this->assertEquals('payee', $ynab->getPayee());
        $this->assertEquals('memo', $ynab->getMemo());
        $this->assertEquals('123.45', $ynab->getOutflowAsString());
        $this->assertEquals('987.65', $ynab->getInflowAsString());
    }
}