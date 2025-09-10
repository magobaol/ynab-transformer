<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Nexi implements Transformer
{
    private const FIRST_DATA_ROW = 11;
    private const COL_TO_CHECK_END = "B";
    private const HEADER_ROW = 10;

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
            
            // Check if this looks like a Nexi file by examining header structure
            $sheet = $testFile->getActiveSheet();
            
            // Check for Nexi-specific header structure in HEADER_ROW (shifted to column B)
            // Expected headers: Mese, Data, Riferimento, Categorie, Descrizione, Stato, Importo originale in Divisa, Divisa, Importo (€), Cambio applicato (€), Commisione Nexi (€), Commissione circuiti (€)
            $expectedHeaders = [
                'B' . self::HEADER_ROW => 'Mese',
                'C' . self::HEADER_ROW => 'Data',
                'D' . self::HEADER_ROW => 'Riferimento',
                'E' . self::HEADER_ROW => 'Categorie',
                'F' . self::HEADER_ROW => 'Descrizione',
                'G' . self::HEADER_ROW => 'Stato',
                'H' . self::HEADER_ROW => 'Importo originale in Divisa',
                'I' . self::HEADER_ROW => 'Divisa',
                'J' . self::HEADER_ROW => 'Importo (€)',
                'K' . self::HEADER_ROW => 'Cambio applicato (€)',
                'L' . self::HEADER_ROW => 'Commisione Nexi (€)',
                'M' . self::HEADER_ROW => 'Commissione circuiti (€)'
            ];
            
            // Check if all headers match Nexi pattern
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
                    $amount > 0 ? abs($amount) : 0,
                    $amount < 0 ? abs($amount) : 0,
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
        $this->file->getActiveSheet()->getStyle('C' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        return Carbon::createFromFormat('d/m/Y', $this->file->getActiveSheet()->getCell('C' . $row)->getFormattedValue());
    }

    /**
     * @param int $row
     * @return mixed
     */
    public function getPayee(int $row): mixed
    {
        return $this->file->getActiveSheet()->getCell('F' . $row)->getValue();
    }

    /**
     * @param int $row
     * @return mixed
     */
    public function getAmount(int $row): mixed
    {
        return $this->file->getActiveSheet()->getCell('J' . $row)->getValue();
    }
}