<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Revolut implements Transformer
{
    private Spreadsheet $file;
    private const FIRST_DATA_ROW = 2;
    private const COL_TO_CHECK_END = "A";

    public function __construct($inputFilename)
    {
        $this->file = IOFactory::load($inputFilename);
    }

    public static function canHandle(string $filename): bool
    {
        // TODO: Implement Revolut detection logic
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
                $memo = '';
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

    /**
     * @param int $row
     * @return bool
     */
    private function rowHasData(int $row): bool
    {
        return $this->file->getActiveSheet()->getCell(self::COL_TO_CHECK_END . $row)->getValue() != '';
    }

    /**
     * @param int $row
     * @return bool
     */
    private function shouldSkipRow(int $row): bool
    {
        return ($this->file->getActiveSheet()->getCell("I".$row)->getValue() != 'COMPLETED');
    }

    /**
     * @param int $row
     * @return Carbon|false
     */
    private function getDate(int $row): Carbon|false
    {
        $this->file->getActiveSheet()->getStyle('C' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->file->getActiveSheet()->getCell('C' . $row)->getFormattedValue());
    }

    private function getPayee(int $row)
    {
       return $this->file->getActiveSheet()->getCell("E".$row)->getValue();
    }

    private function getAmount(int $row)
    {
        //Amount is in column F and can be positive or negative
        //Fee is in column G and is always positive, so I need to subtract it from the amount
        return ($this->file->getActiveSheet()->getCell('F' . $row)->getValue()) -
            ($this->file->getActiveSheet()->getCell('G' . $row)->getValue());
    }
}