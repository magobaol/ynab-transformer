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
    private const HEADER_ROW = 1;

    public function __construct($inputFilename)
    {
        $reader = IOFactory::createReader('Csv');
        $reader->setDelimiter(';');
        $reader->setTestAutoDetect(true);
        $this->file = $reader->load($inputFilename);
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
            
            // Try to load the file as a CSV with semicolon delimiter
            $reader = IOFactory::createReader('Csv');
            $reader->setDelimiter(';');
            $reader->setTestAutoDetect(true);
            $testFile = $reader->load($filename);
            
            // Check if this looks like a Popso file by examining header structure
            $sheet = $testFile->getActiveSheet();
            
            // Check for Popso-specific header structure in HEADER_ROW
            // Expected headers: Data, Valuta, Causale, Descrizione, Importo, Divisa
            $expectedHeaders = [
                'A' . self::HEADER_ROW => 'Data',
                'B' . self::HEADER_ROW => 'Valuta',
                'C' . self::HEADER_ROW => 'Causale',
                'D' . self::HEADER_ROW => 'Descrizione',
                'E' . self::HEADER_ROW => 'Importo',
                'F' . self::HEADER_ROW => 'Divisa'
            ];
            
            // Check if all headers match Popso pattern
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