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
    private const HEADER_ROW = 12;

    private Spreadsheet $file;

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
            
            // Check if this looks like a Poste file by examining header structure
            $sheet = $testFile->getActiveSheet();
            
            // Check for Poste-specific header structure in HEADER_ROW
            // Expected headers: Data Contabile, Data Valuta, Addebiti (euro), Accrediti (euro), Descrizione operazioni
            $expectedHeaders = [
                'A' . self::HEADER_ROW => 'Data Contabile',
                'B' . self::HEADER_ROW => 'Data Valuta',
                'C' . self::HEADER_ROW => 'Addebiti (euro)',
                'D' . self::HEADER_ROW => 'Accrediti (euro)',
                'E' . self::HEADER_ROW => 'Descrizione operazioni'
            ];
            
            // Check if all headers match Poste pattern
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