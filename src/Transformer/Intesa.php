<?php

namespace Transformer;

use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Intesa implements Transformer
{
    private Spreadsheet $file;

    private const SHEET_TITLE = 'Lista Operazione';
    private const HEADER_ROW = 18;
    private const FIRST_DATA_ROW = 19;

    private const COL_DATA = 'A';
    private const COL_OPERAZIONE = 'B';
    private const COL_DETTAGLI = 'C';
    private const COL_CONTO = 'D';
    private const COL_CONTABILIZZAZIONE = 'E';
    private const COL_CATEGORIA = 'F';
    private const COL_VALUTA = 'G';
    private const COL_IMPORTO = 'H';

    private const EXPECTED_HEADERS = [
        self::COL_DATA => 'Data',
        self::COL_OPERAZIONE => 'Operazione',
        self::COL_DETTAGLI => 'Dettagli',
        self::COL_CONTO => 'Conto o carta',
        self::COL_CONTABILIZZAZIONE => 'Contabilizzazione',
        self::COL_IMPORTO => 'Importo',
    ];

    private const STATO_CONTABILIZZATO = 'CONTABILIZZATO';

    public function __construct(string $inputFilename)
    {
        $this->file = IOFactory::load($inputFilename);
    }

    public static function canHandle(string $filename): bool
    {
        try {
            if (!file_exists($filename)) {
                return false;
            }

            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'xlsx') {
                return false;
            }

            $testFile = IOFactory::load($filename);
            $sheet = $testFile->getActiveSheet();

            if ($sheet->getTitle() !== self::SHEET_TITLE) {
                return false;
            }

            return self::headersMatch($sheet);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function headersMatch(Worksheet $sheet): bool
    {
        foreach (self::EXPECTED_HEADERS as $col => $expected) {
            if ($sheet->getCell($col . self::HEADER_ROW)->getValue() !== $expected) {
                return false;
            }
        }
        return true;
    }

    public function transformToYNAB(): YNABTransactions
    {
        $transactions = new YNABTransactions();
        $sheet = $this->file->getActiveSheet();
        $row = self::FIRST_DATA_ROW;

        while ($this->rowHasData($sheet, $row)) {
            if ($this->isContabilizzato($sheet, $row)) {
                $date = $this->getDate($sheet, $row);
                $amount = (float) $sheet->getCell(self::COL_IMPORTO . $row)->getValue();

                $transactions->add(YNABTransaction::fromStrings(
                    $date->format('Y-m-d'),
                    $this->getPayee($sheet, $row),
                    '',
                    $amount < 0 ? number_format(abs($amount), 2, '.', '') : '0',
                    $amount > 0 ? number_format($amount, 2, '.', '') : '0',
                ));
            }
            $row++;
        }

        return $transactions;
    }

    private function rowHasData(Worksheet $sheet, int $row): bool
    {
        $value = $sheet->getCell(self::COL_DATA . $row)->getValue();
        return is_numeric($value);
    }

    private function isContabilizzato(Worksheet $sheet, int $row): bool
    {
        return $sheet->getCell(self::COL_CONTABILIZZAZIONE . $row)->getValue() === self::STATO_CONTABILIZZATO;
    }

    private function getDate(Worksheet $sheet, int $row): \DateTimeInterface
    {
        $serial = $sheet->getCell(self::COL_DATA . $row)->getValue();
        return SpreadsheetDate::excelToDateTimeObject((float) $serial);
    }

    private function getPayee(Worksheet $sheet, int $row): string
    {
        $payee = (string) $sheet->getCell(self::COL_OPERAZIONE . $row)->getValue();
        return trim(preg_replace('/\s+/', ' ', $payee));
    }
}
