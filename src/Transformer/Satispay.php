<?php

namespace Transformer;

use Carbon\Carbon;
use Model\Transaction\YNABTransaction;
use Model\Transaction\YNABTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Satispay implements Transformer
{
    private const SHEET_NAME = 'Transactions';
    private const HEADER_ROW = 1;
    private const FIRST_DATA_ROW = 2;
    private const COL_TO_CHECK_END = 'A';
    private const STATUS_APPROVED = '✅ Approvato';

    private Worksheet $sheet;

    public function __construct($inputFilename)
    {
        $this->sheet = IOFactory::load($inputFilename)->getSheetByName(self::SHEET_NAME);
    }

    public static function canHandle(string $filename): bool
    {
        try {
            if (!file_exists($filename)) {
                return false;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension !== 'xlsx') {
                return false;
            }

            $sheet = IOFactory::load($filename)->getSheetByName(self::SHEET_NAME);
            if ($sheet === null) {
                return false;
            }

            // Expected headers: Data, Nome, Descrizione, Importo, Tipo, Stato
            $expectedHeaders = [
                'A' . self::HEADER_ROW => 'Data',
                'B' . self::HEADER_ROW => 'Nome',
                'C' . self::HEADER_ROW => 'Descrizione',
                'D' . self::HEADER_ROW => 'Importo',
                'E' . self::HEADER_ROW => 'Tipo',
                'F' . self::HEADER_ROW => 'Stato',
            ];

            foreach ($expectedHeaders as $cell => $expectedValue) {
                $actualValue = trim($sheet->getCell($cell)->getValue() ?? '');
                if ($actualValue !== $expectedValue) {
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

    private function rowHasData(int $row): bool
    {
        return $this->sheet->getCell(self::COL_TO_CHECK_END . $row)->getValue() != '';
    }

    private function shouldSkipRow(int $row): bool
    {
        return $this->sheet->getCell('F' . $row)->getValue() !== self::STATUS_APPROVED;
    }

    private function getDate(int $row): Carbon
    {
        return Carbon::instance(Date::excelToDateTimeObject($this->sheet->getCell('A' . $row)->getValue()));
    }

    private function getPayee(int $row): string
    {
        return $this->sheet->getCell('B' . $row)->getValue();
    }

    private function getAmount(int $row): float
    {
        return $this->sheet->getCell('D' . $row)->getValue();
    }
}
