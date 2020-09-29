<?php

declare(strict_types=1);

namespace Buysera\CommissionTask;


class Fees    
{
    private $rateUSD = 1.1497;
    private $rateJPY = 129.53;
    private static $data;

    public static function getData(array $data) : void
    {
        self::$data = $data;
    }

    public function countCashIn() 
    {
        
    }

    // Commission fee - 0.03% from total amount, but no more than 5.00 EUR.
    public function cashIn(float $amount, string $currency) {
        $minFee = 5;
        $commissionRate = 0.03;
        $commission = $amount/100 * $commissionRate;
        $currency == 'JPY' ? $precision = 0 : $precision = 2;        
        if ($commission > $minFee && $currency == 'EUR') {
            $commission = $minFee;
        } elseif ($currency == 'USD' && ($commission/$this->rateUSD) > 5) {
            $commission = $minFee * $this->rateUSD;
        } elseif ($currency == 'JPY' && ($commission/$this->rateJPY) > 5) {
            $commission = $minFee * $this->rateJPY;
        }
        return self::roundUp($commission, $precision);
    }

    public static function roundUp($value, $precision) {
        $mult = pow(10, $precision);
        return ceil($value * $mult) / $mult;
    }


}