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
    private const FIRST_DATA_ROW = 11;
    private const COL_TO_CHECK_END = "A";
    private const HEADER_ROW = 10;

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
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension !== 'xlsx') {
                return false;
            }
            
            // Try to load the file as a spreadsheet
            $testFile = IOFactory::load($filename);
            
            // Check if this looks like a Fineco file by examining header structure
            $sheet = $testFile->getActiveSheet();
            
            // Check for Fineco-specific header structure in HEADER_ROW
            // Expected headers: Data, Entrate, Uscite, Descrizione, Descrizione Completa, Stato, Moneymap
            $expectedHeaders = [
                'A' . self::HEADER_ROW => 'Data_Operazione',
                'B' . self::HEADER_ROW => 'Data_Valuta',
                'C' . self::HEADER_ROW => 'Entrate',
                'D' . self::HEADER_ROW => 'Uscite',
                'E' . self::HEADER_ROW => 'Descrizione',
                'F' . self::HEADER_ROW => 'Descrizione_Completa',
                'G' . self::HEADER_ROW => 'Stato'
            ];
            
            // Check if all headers match Fineco pattern
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
        return $this->file->getActiveSheet()->getCell('F' . $row)->getValue() ?? '';
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
        return ($this->file->getActiveSheet()->getCell('G' . $row)->getValue() != 'Contabilizzato');
    }

    private function getAmount(int $row)
    {
        if ($this->file->getActiveSheet()->getCell('C' . $row)->getValue() != '') {
            return $this->file->getActiveSheet()->getCell('C' . $row)->getValue();
        } else {
            return $this->file->getActiveSheet()->getCell('D' . $row)->getValue();
        }
    }

    private function getMemo(int $row, Carbon $accountingDate): string
    {
        $memo = '';

        //Data valuta
        $this->file->getActiveSheet()->getStyle('B' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        $valueDate = Carbon::createFromFormat('d/m/Y', $this->file->getActiveSheet()->getCell('B' . $row)->getFormattedValue())->setTime(0,0,0);

        $description = $this->file->getActiveSheet()->getCell('F' . $row)->getValue();

        if (($valueDate) && ($valueDate != $accountingDate)) {
            $memo .= sprintf('(%s) ', $valueDate->format('d/m/Y'));
        }

        if (str_contains(strtolower($description ?? ''), 'prelevamento')) {
            $memo .= 'Prelievo';
        } else {
            $memo .= $this->file->getActiveSheet()->getCell('F' . $row)->getValue();
        }
        return trim($memo);
    }

}