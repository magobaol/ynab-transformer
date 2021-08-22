<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Popso implements Transformer
{
    private Spreadsheet $file;
    private const FIRST_DATA_ROW = 2;
    private const COL_TO_CHECK_END = "A";

    public function __construct($inputFilename)
    {
        $this->file = IOFactory::load($inputFilename);
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

    private function shouldSkipRow(int $row): bool
    {
        return in_array(
            $this->file->getActiveSheet()->getCell("C".$row)->getValue(),
            [
                "***** SALDO INIZIALE ******",
                "****** SALDO FINALE *******"
            ]
        );
    }

    /**
     * @param int $row
     * @return Carbon|false
     */
    public function getDate(int $row): Carbon|false
    {
        $this->file->getActiveSheet()->getStyle('A' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        return Carbon::createFromFormat('d/m/Y', $this->file->getActiveSheet()->getCell('A' . $row)->getFormattedValue());
    }

    /**
     * @param int $row
     * @return mixed
     */
    public function getPayee(int $row): mixed
    {
        return $this->file->getActiveSheet()->getCell('D' . $row)->getValue();
    }

    /**
     * @param int $row
     * @return string|array
     */
    public function getAmount(int $row): string|array
    {
        $amount = $this->file->getActiveSheet()->getCell('E' . $row)->getValue();
        return str_replace(',', '.', $amount);
    }

    /**
     * @param int $row
     * @return bool
     */
    public function rowHasData(int $row): bool
    {
        return $this->file->getActiveSheet()->getCell(self::COL_TO_CHECK_END . $row)->getValue() != '';
    }

}