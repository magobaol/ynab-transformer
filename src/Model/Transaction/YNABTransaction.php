<?php

namespace Model\Transaction;

use Carbon\Carbon;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;
use Money\Parser\DecimalMoneyParser;

class YNABTransaction
{
    private \DateTimeInterface $date;
    private string $payee;
    private string $memo;
    private Money $outflow;
    private Money $inflow;

    private ISOCurrencies $ISOCurrencies;
    private MoneyFormatter $moneyFormatter;

    const FORMAT = 'Y-m-d';

    private function __construct($date, $payee, $memo, $outflow, $inflow)
    {
        $this->date = $date;
        $this->payee = $payee;
        $this->memo = $memo;
        $this->outflow = $outflow;
        $this->inflow = $inflow;
        $this->ISOCurrencies = new ISOCurrencies();
        $this->moneyFormatter = new DecimalMoneyFormatter($this->ISOCurrencies);
    }

    public static function fromStrings($date, $payee, $memo, $outflow, $inflow): YNABTransaction
    {
        $currencies = new ISOCurrencies();
        $moneyParser = new DecimalMoneyParser($currencies);

        return new self(
            Carbon::createFromFormat(self::FORMAT, $date),
            $payee,
            $memo,
            $moneyParser->parse($outflow, new Currency('EUR')),
            $moneyParser->parse($inflow, new Currency('EUR')),
        );
    }

    public function toArray(): array
    {
       return [
            'date' => $this->date->format(self::FORMAT),
            'payee' => $this->payee,
            'memo' => $this->memo,
            'outflow' => $this->moneyFormatter->format($this->outflow),
            'inflow' => $this->moneyFormatter->format($this->inflow),
        ];
    }

    /**
     * @return string
     */
    public function getPayee(): string
    {
        return $this->payee;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getMemo(): string
    {
        return $this->memo;
    }

    public function getOutflowAsString(): string
    {
        return $this->moneyFormatter->format($this->outflow);
    }

    public function getInflowAsString(): string
    {
        return $this->moneyFormatter->format($this->inflow);
    }

}
