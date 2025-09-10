<?php

namespace Transformer;

use Carbon\Carbon;
use Common\DateExtractor;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Fineco implements Transformer
{
    private Spreadsheet $file;
    private const FIRST_DATA_ROW = 8;
    private const COL_TO_CHECK_END = "A";

    public function __construct($inputFilename)
    {
        $this->file = IOFactory::load($inputFilename);
    }

    public static function canHandle(string $filename): bool
    {
        // TODO: Implement Fineco detection logic
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

    /**
     * @param int $row
     * @return Carbon|false
     */
    public function getDate(int $row): Carbon|false
    {
        $this->file->getActiveSheet()->getStyle('A' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        return Carbon::createFromFormat('d/m/Y', $this->file->getActiveSheet()->getCell('A' . $row)->getFormattedValue())->setTime(0,0,0);
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
     * @return bool
     */
    public function rowHasData(int $row): bool
    {
        return $this->file->getActiveSheet()->getCell(self::COL_TO_CHECK_END . $row)->getValue() != '';
    }

    private function shouldSkipRow(int $row): bool
    {
        return ($this->file->getActiveSheet()->getCell('F' . $row)->getValue() != 'Contabilizzato');
    }

    private function getAmount(int $row)
    {
        if ($this->file->getActiveSheet()->getCell('B' . $row)->getValue() != '') {
            return $this->file->getActiveSheet()->getCell('B' . $row)->getValue();
        } else {
            return $this->file->getActiveSheet()->getCell('C' . $row)->getValue();
        }
    }

    private function getMemo(int $row, Carbon $accountingDate): string
    {
        $memo = '';
        $description = $this->file->getActiveSheet()->getCell('E' . $row)->getValue();
        $foundDate = DateExtractor::extractFromString($description);

        if (($foundDate) && ($foundDate != $accountingDate)) {
            $memo .= sprintf('(%s) ', $foundDate->format('d/m/Y'));
        }

        if (str_contains(strtolower($description), 'prelevamento')) {
            $memo .= 'Prelievo';
        } else {
            $memo .= $this->file->getActiveSheet()->getCell('E' . $row)->getValue();
        }
        return trim($memo);
    }

}