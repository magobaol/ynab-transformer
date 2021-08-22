<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class NexiToYNAB
{
    private const FIRST_DATA_ROW = 11;
    private const COL_TO_CHECK_END = "B";

    private Spreadsheet $nexiFile;

    public function __construct($inputFilename)
    {
        $this->nexiFile = IOFactory::load($inputFilename);
    }

    public function process(): YNABTransactions
    {
        $YNABTransactions = new YNABTransactions();

        $row = self::FIRST_DATA_ROW;
        $end = false;
        while (!$end) {
            if ($this->nexiFile->getActiveSheet()->getCell(self::COL_TO_CHECK_END.$row)->getValue() == '') {
                $end = true;
            } else {

                $this->nexiFile->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                $date = Carbon::createFromFormat('d/m/Y', $this->nexiFile->getActiveSheet()->getCell('C'.$row)->getFormattedValue());

                $payee = $this->nexiFile->getActiveSheet()->getCell('F'.$row)->getValue();
                $memo = '';
                $amount = $this->nexiFile->getActiveSheet()->getCell('J'.$row)->getValue();
                $transaction = YNABTransaction::fromStrings(
                    $date->format('Y-m-d'),
                    $payee,
                    $memo,
                    $amount > 0 ? abs($amount) : 0,
                    $amount < 0 ? abs($amount) : 0,
                );
                $YNABTransactions->add($transaction);
                $row++;
            }
        }

        return $YNABTransactions;
    }
}