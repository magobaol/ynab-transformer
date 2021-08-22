<?php

namespace Tests\Model;

use Model\NexiToYNAB;
use PHPUnit\Framework\TestCase;

class NexiToYNABTest extends TestCase
{
    public function test_process()
    {
        $nexiToYNAB = new NexiToYNAB(__DIR__.'/../Fixtures/movimenti-nexi.xlsx');
        $YNABTransactions = $nexiToYNAB->process();

        $transactionsArray = $YNABTransactions->toArray();

        $this->assertCount(3, $transactionsArray);

        $this->assertEquals('2021-08-13', $YNABTransactions->getByIndex(0)->getDate()->format('Y-m-d'));
        $this->assertEquals('GRUPPO SDA SRL', $YNABTransactions->getByIndex(0)->getPayee());
        $this->assertEquals('0.00', $YNABTransactions->getByIndex(0)->getInflowAsString());
        $this->assertEquals('33.88', $YNABTransactions->getByIndex(0)->getOutflowAsString());

        $this->assertEquals('2021-08-03', $YNABTransactions->getByIndex(1)->getDate()->format('Y-m-d'));
        $this->assertEquals('AMZ*Amazon.it', $YNABTransactions->getByIndex(1)->getPayee());
        $this->assertEquals('53.25', $YNABTransactions->getByIndex(1)->getInflowAsString());
        $this->assertEquals('0.00', $YNABTransactions->getByIndex(1)->getOutflowAsString());

        $this->assertEquals('2021-08-03', $YNABTransactions->getByIndex(2)->getDate()->format('Y-m-d'));
        $this->assertEquals('Subscription PRO', $YNABTransactions->getByIndex(2)->getPayee());
        $this->assertEquals('0.00', $YNABTransactions->getByIndex(2)->getInflowAsString());
        $this->assertEquals('1.71', $YNABTransactions->getByIndex(2)->getOutflowAsString());
    }
}