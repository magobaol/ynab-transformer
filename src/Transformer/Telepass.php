<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Telepass implements Transformer
{
    private Spreadsheet $file;
    private const FIRST_DATA_ROW = 18;
    private const COL_TO_CHECK_END = "A";

    public function __construct($inputFilename)
    {
        $this->file = IOFactory::load($inputFilename);
    }

    public static function canHandle(string $filename): bool
    {
        // TODO: Implement Telepass detection logic
        return false;
    }

    public function transformToYNAB(): YNABTransactions
    {
        $YNABTransactions = new YNABTransactions();
        $row = self::FIRST_DATA_ROW;

        while ($this->rowHasData($row)) {
            if (!$this->shouldSkipRow($row)) {
                $date = $this->getDate($row);
                $payee = $this->getPayee($row);
                $memo = $this->getMemo($row, $date);
                $amount = $this->getAmount($row);

                $transaction = YNABTransaction::fromStrings(
                    $date->format('Y-m-d'),
                    $payee,
                    $memo,
                    $amount < 0 ? abs($amount) : 0,
                    $amount > 0 ? abs($amount) : 0,
                );
                $YNABTransactions->add($transaction);

            }
            $row++;

        }

        return $YNABTransactions;

    }

    private function rowHasData(int $row)
    {
        return $this->file->getActiveSheet()->getCell(self::COL_TO_CHECK_END . $row)->getValue() != '';
    }

    private function shouldSkipRow(int $row)
    {
        return false;
    }

    private function getDate(int $row)
    {
        $date = substr($this->file->getActiveSheet()->getCell('C' . $row)->getValue(), 0, 10);
        return Carbon::createFromFormat('d-m-Y', $date)->setTime(0,0,0);
    }

    private function getPayee(int $row)
    {
        return "Telepass";
    }

    private function getMemo(int $row, bool|Carbon $date)
    {
        return trim($this->file->getActiveSheet()->getCell('D' . $row)->getValue());
    }

    private function getAmount(int $row)
    {
        return $this->file->getActiveSheet()->getCell('F' . $row)->getValue() * -1;
    }
}