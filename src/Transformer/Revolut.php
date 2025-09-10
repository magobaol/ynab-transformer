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
    private const HEADER_ROW = 1;

    public function __construct($inputFilename)
    {
        $this->file = IOFactory::load($inputFilename);
    }

    public static function canHandle(string $filename): bool
    {
        try {
            // Check if file exists first
            if (!file_exists($filename)) {
                return false;
            }
            
            // Quick file extension check to avoid trying to process obvious non-CSV files
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                return false;
            }
            
            // Try to load the file as a spreadsheet
            $testFile = IOFactory::load($filename);
            
            // Check if this looks like a Revolut file by examining header structure
            $sheet = $testFile->getActiveSheet();
            
            // Check for Revolut-specific header structure in HEADER_ROW
            // Expected headers: Type, Product, Started Date, Completed Date, Description, Amount, Fee, Currency, State, Balance
            $expectedHeaders = [
                'A' . self::HEADER_ROW => 'Type',
                'B' . self::HEADER_ROW => 'Product',
                'C' . self::HEADER_ROW => 'Started Date',
                'D' . self::HEADER_ROW => 'Completed Date',
                'E' . self::HEADER_ROW => 'Description',
                'F' . self::HEADER_ROW => 'Amount',
                'G' . self::HEADER_ROW => 'Fee',
                'H' . self::HEADER_ROW => 'Currency',
                'I' . self::HEADER_ROW => 'State',
                'J' . self::HEADER_ROW => 'Balance'
            ];
            
            // Check if all headers match Revolut pattern
            foreach ($expectedHeaders as $cell => $expectedValue) {
                if ($sheet->getCell($cell)->getValue() !== $expectedValue) {
                    return false;
                }
            }
            
            return true;
            
        } catch (\Throwable $e) {
            // Silent failure for format detection - this is expected behavior
            return false;
        }
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