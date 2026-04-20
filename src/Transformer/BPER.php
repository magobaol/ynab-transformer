<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BPER implements Transformer
{
    private Spreadsheet $file;

    private const SHEET_TITLE = 'Movimenti Conto';
    private const HEADER_ROW = 18;
    private const FIRST_DATA_ROW = 19;

    private const COL_DATA_OPERAZIONE = 'B';
    private const COL_DATA_VALUTA = 'C';
    private const COL_DESCRIZIONE = 'D';
    private const COL_ENTRATE = 'E';
    private const COL_USCITE = 'F';
    private const COL_STATO = 'H';

    private const EXPECTED_HEADERS = [
        self::COL_DATA_OPERAZIONE => 'Data operazione',
        self::COL_DATA_VALUTA => 'Data valuta',
        self::COL_DESCRIZIONE => 'Descrizione',
        self::COL_ENTRATE => 'Entrate',
        self::COL_USCITE => 'Uscite',
        self::COL_STATO => 'Stato',
    ];

    private const DATE_FORMAT = '!d F Y';
    private const DATE_LOCALE = 'it';

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

            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'xls') {
                return false;
            }

            $testFile = IOFactory::load($filename);
            $sheet = $testFile->getActiveSheet();

            if ($sheet->getTitle() !== self::SHEET_TITLE) {
                return false;
            }

            foreach (self::EXPECTED_HEADERS as $col => $expected) {
                if ($sheet->getCell($col . self::HEADER_ROW)->getValue() !== $expected) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function transformToYNAB(): YNABTransactions
    {
        $transactions = new YNABTransactions();
        $row = self::FIRST_DATA_ROW;

        while ($this->rowHasData($row)) {
            if (!$this->shouldSkipRow($row)) {
                $operationDate = $this->getDate($row, self::COL_DATA_OPERAZIONE);
                $valueDate = $this->getDate($row, self::COL_DATA_VALUTA);
                $amount = $this->getAmount($row);

                $transactions->add(YNABTransaction::fromStrings(
                    $operationDate->format('Y-m-d'),
                    $this->getPayee($row),
                    $this->getMemo($operationDate, $valueDate),
                    $amount < 0 ? (string) abs($amount) : '0',
                    $amount > 0 ? (string) abs($amount) : '0',
                ));
            }
            $row++;
        }

        return $transactions;
    }

    private function rowHasData(int $row): bool
    {
        return $this->parseItalianDate(
            $this->file->getActiveSheet()->getCell(self::COL_DATA_OPERAZIONE . $row)->getValue()
        ) !== null;
    }

    private function shouldSkipRow(int $row): bool
    {
        return $this->file->getActiveSheet()->getCell(self::COL_STATO . $row)->getValue() !== 'Contabilizzato';
    }

    private function getDate(int $row, string $column): Carbon
    {
        $raw = $this->file->getActiveSheet()->getCell($column . $row)->getValue();
        $date = $this->parseItalianDate($raw);
        if ($date === null) {
            throw new \RuntimeException("Unable to parse BPER date '{$raw}' at row {$row}, column {$column}");
        }
        return $date;
    }

    private function parseItalianDate(mixed $raw): ?Carbon
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
        try {
            return Carbon::createFromLocaleFormat(self::DATE_FORMAT, self::DATE_LOCALE, trim($raw));
        } catch (\Throwable) {
            return null;
        }
    }

    private function getPayee(int $row): string
    {
        $description = (string) $this->file->getActiveSheet()->getCell(self::COL_DESCRIZIONE . $row)->getValue();
        return trim(preg_replace('/\s+/', ' ', $description));
    }

    private function getMemo(Carbon $operationDate, Carbon $valueDate): string
    {
        return $valueDate->equalTo($operationDate)
            ? ''
            : sprintf('(%s)', $valueDate->format('d/m/Y'));
    }

    private function getAmount(int $row): float
    {
        $sheet = $this->file->getActiveSheet();
        $inflow = $sheet->getCell(self::COL_ENTRATE . $row)->getValue();
        $outflow = $sheet->getCell(self::COL_USCITE . $row)->getValue();

        if ($inflow !== null && $inflow !== '') {
            return (float) $inflow;
        }
        return (float) $outflow;
    }
}
