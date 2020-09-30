<?php

declare(strict_types=1);

namespace Buysera\CommissionTask;

use Carbon\Carbon;

class Fees    
{
    private $rateUSD = 1.1497;
    private $rateJPY = 129.53;
    private static $data;

    public static function getData(array $data) : void
    {
        self::$data = $data;
    }

    public function commissionCalc() 
    {
        foreach (self::$data as $key => $value) {
            if ($value[3] == 'cash_in') {
                $this->cashIn((float)$value[4], $value[5]);
            }
        }
    }

    // Commission fee - 0.03% from total amount, but no more than 5.00 EUR.
    public function cashIn(float $amount, string $currency) : float
    {
        $minFee = 5;
        $commissionRate = 0.03;
        $commission = $amount/100 * $commissionRate;
        $currency == 'JPY' ? $precision = 0 : $precision = 2;        
        if ($commission > $minFee && $currency == 'EUR') {
            $commission = $minFee;
        } elseif ($currency == 'USD' && ($commission/$this->rateUSD) > $minFee) {
            $commission = $minFee * $this->rateUSD;
        } elseif ($currency == 'JPY' && ($commission/$this->rateJPY) > $minFee) {
            $commission = $minFee * $this->rateJPY;
        }
        return self::roundUp($commission, $precision);
    }

    public function cashOutNat(/*float $amount, string $currency, string $date*/) //: float
    {
        $commissionRate = 0.3;
        $discountAmount = 1000.00;
        $week = Carbon::parse($date)->isoWeek();
    }

    public function cashOutLegal(float $amount, string $currency) : float
    {
        $commissionRate = 0.3;
        $minFee = 0.50;
        
    }

    // rounds to the smallest currency item (cents for USD and EUR, yen for JPY)
    public static function roundUp(float $value, int $precision) : float
    {
        $mult = pow(10, $precision);
        return ceil($value * $mult) / $mult;
    }


}