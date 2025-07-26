<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Poste implements Transformer
{
    private const FIRST_DATA_ROW = 13;
    private const COL_TO_CHECK_END = "A";

    private Spreadsheet $file;

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

    /**
     * @param int $row
     * @return bool
     */
    public function rowHasData(int $row): bool
    {
        return $this->file->getActiveSheet()->getCell(self::COL_TO_CHECK_END . $row)->getValue() != '';
    }

    private function shouldSkipRow(int $row)
    {
        return ($this->file->getActiveSheet()->getCell('G' . $row)->getValue() == 'Non Contabilizzato');
    }

    /**
     * @param int $row
     * @return Carbon|false
     */
    public function getDate(int $row): Carbon|false
    {
        //Accrediti del fido hanno valore -1 nella data valuta, quindi si prende la data contabile
        if ($this->file->getActiveSheet()->getCell('B' . $row)->getValue() == -1) {
            $this->file->getActiveSheet()->getStyle('A' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
            return Carbon::createFromFormat('d/m/Y', $this->file->getActiveSheet()->getCell('A' . $row)->getFormattedValue());
        } else {
            $this->file->getActiveSheet()->getStyle('B' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
            return Carbon::createFromFormat('d/m/Y', $this->file->getActiveSheet()->getCell('B' . $row)->getFormattedValue());
        }
    }

    /**
     * @param int $row
     * @return mixed
     */
    public function getPayee(int $row): mixed
    {
        return $this->file->getActiveSheet()->getCell('E' . $row)->getValue();
    }

    /**
     * @param int $row
     * @return mixed
     */
    public function getAmount(int $row): mixed
    {
        if ($this->file->getActiveSheet()->getCell('C' . $row)->getValue() != '') {
            return ($this->file->getActiveSheet()->getCell('C' . $row)->getValue() * -1);
        } else {
            return $this->file->getActiveSheet()->getCell('D' . $row)->getValue();
        }
    }
}