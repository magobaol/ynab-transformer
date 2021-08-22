<?php

namespace Model\Transaction;

class YNABTransactions
{
    private array $transactions;

    public function __construct()
    {
        $this->transactions = [];
    }

    public function add(YNABTransaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    public function getByIndex($index): YNABTransaction
    {
        return $this->transactions[$index];
    }

    public function toArray(): array
    {
        return array_map(function (YNABTransaction $transaction) { return $transaction->toArray(); }, $this->transactions);
    }

    public function toCSV(): string
    {
        $data = "Date;Payee;Memo;Outflow;Inflow".PHP_EOL;

        foreach ($this->transactions as $transaction) {
            $data .= implode(";",$transaction->toArray()).PHP_EOL;
        }

        return $data;
    }

    public function toCSVFile($filename)
    {
        file_put_contents($filename, $this->toCSV());
    }
}