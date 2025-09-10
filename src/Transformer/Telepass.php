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
    private const HEADER_ROW = 17;

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
            
            // Quick file extension check to avoid trying to process obvious non-Excel files
            // Telepass uses the older .xls format, not .xlsx
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension !== 'xls') {
                return false;
            }
            
            // Try to load the file as a spreadsheet
            $testFile = IOFactory::load($filename);
            
            // Check if this looks like a Telepass file by examining header structure
            $sheet = $testFile->getActiveSheet();
            
            // Check for Telepass-specific header structure in HEADER_ROW
            // Expected headers: Dispositivo, Numero dispositivo, Data e ora, Descrizione, Classe, Importo
            $expectedHeaders = [
                'A' . self::HEADER_ROW => 'Dispositivo',
                'B' . self::HEADER_ROW => 'Numero dispositivo',
                'C' . self::HEADER_ROW => 'Data e ora',
                'D' . self::HEADER_ROW => 'Descrizione',
                'E' . self::HEADER_ROW => 'Classe',
                'F' . self::HEADER_ROW => 'Importo'
            ];
            
            // Check if all headers match Telepass pattern
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