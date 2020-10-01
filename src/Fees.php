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

    public function commissionCalc() : array
    {
        $results = [];
        foreach (self::$data as $key => $value) {
            $date = $value[0];
            $client_id = 
            $client_type = $value[2];
            $operation = $value[3];
            $amount = $value[4];
            $currency = $value[5];            
            
            if ($value[3] == 'cash_in') {
                $total = (array)$this->cashIn((float)$amount, $currency);
            } elseif ($operation == 'cash_out' && $client_type == 'natural') {
                $total = (array)$this->cashOutNat((float)$amount, $currency, $date);
            } elseif ($operation == 'cash_out' && $client_type == 'legal') {
                $total = (array)$this->cashOutLegal((float)$amount, $currency);
            } 

            $results[] = $total;            
        }
        echo '<pre>';
        print_r($results);
        return $results;
    }

    // Commission fee - 0.03% from total amount, but no more than 5.00 EUR.
    public function cashIn(float $amount, string $currency) : string
    {
        $minFee = 5;
        $commissionRate = 0.03;

        $commission = $amount/100 * $commissionRate;   

        if ($commission > $minFee && $currency == 'EUR') {
            $commission = $minFee;
        } elseif ($currency == 'USD' && ($commission/$this->rateUSD) > $minFee) {
            $commission = $minFee * $this->rateUSD;
        } elseif ($currency == 'JPY' && ($commission/$this->rateJPY) > $minFee) {
            $commission = $minFee * $this->rateJPY;
        }

        return self::roundUp($commission, $currency);
    }

    public function cashOutNat(float $amount, string $currency, string $date) : string
    {
        $commissionRate = 0.3;
        $discountAmount = 1000.00;

        $parseDate = Carbon::parse($date);
        $week = $parseDate->isoWeek();
        $year = $parseDate->isoFormat('YYYY'); 

        if ($currency == 'EUR' && $amount > $discountAmount) {
            $commission = ($amount - $discountAmount)/100 * $commissionRate;
        } elseif ($currency == 'USD' && $amount > $discountAmount*$this->rateUSD) {
            $commission = ($amount - $discountAmount*$this->rateUSD)/100 * $commissionRate;
        } elseif ($currency == 'JPY' && $amount > $discountAmount*$this->rateJPY) {
            $commission = ($amount - $discountAmount*$this->rateJPY)/100 * $commissionRate;
        }
        else {
            // if ($currency == 'EUR') {}
            // foreach ($this->countCashOutNat() as $key => $clientCashOuts) {
                $totalAmount = 0; //in EUR
            //     foreach ($clientCashOuts as $key => $value) {
            //         if ($totalAmount < $discountAmount) {
            //             $commission = 0;
            //             $totalAmount += $value[4];
            //         } else {
            //              $commission = 0;
            //         }
            //     }
            // }

            $commission = 0;
        }

        return self::roundUp($commission, $currency);
    }

    public function countNatClientIds() : array
    {
        $clientIds = [];
        foreach (self::$data as $key => $value) {
            if ($value[2] == 'natural'){
                $clientIds[] = $value[1];
            }
        }
        $clientIds = array_unique($clientIds);
        asort($clientIds);
        return $clientIds;
    }

    public function countCashOutNat() : array
    {    
        $cashOuts = [];
        foreach ($this->countNatClientIds() as $key => $id) {
            $clientCashOuts = [];
            foreach (self::$data as $key => $value) {
                if ($value[1] == $id && $value[3] == 'cash_out') {
                    $clientCashOuts[] = $value;
                }
            }
            $cashOuts[] = $clientCashOuts;
        }
        echo '<pre>';
        print_r($cashOuts);
        return $cashOuts;
    }

    public function cashOutLegal(float $amount, string $currency) : string
    {
        $commissionRate = 0.3;
        $minFee = 0.50;

        $commission = $amount/100 * $commissionRate;

        if ($commission < $minFee && $currency == 'EUR') {
            $commission = $minFee;
        } elseif ($currency == 'USD' && ($commission/$this->rateUSD) < $minFee) {
            $commission = $minFee * $this->rateUSD;
        } elseif ($currency == 'JPY' && ($commission/$this->rateJPY) < $minFee) {
            $commission = $minFee * $this->rateJPY;
        }

        return self::roundUp($commission, $currency);
    }
 
    // rounds to the smallest currency item (cents for USD and EUR, yen for JPY)
    public static function roundUp(float $value, string $currency) : string
    {
        $currency == 'JPY' ? $precision = 0 : $precision = 2;

        $mult = pow(10, $precision);

        if ($currency != 'JPY') {
            return number_format(ceil($value * $mult) / $mult, 2, '.', '');
        } else {
            return number_format(ceil($value * $mult) / $mult, 0, '.', '');
        }
    }


}