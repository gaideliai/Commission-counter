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

    public static function indexData() : array
    {
        $i = 0;
        foreach (self::$data as $key => $value) {
            self::$data[$key]['id'] = $i;
            $i++;
        }
        // echo '<pre>';
        // print_r(self::$data);
        return self::$data;
    }

    public function commissionCalc() : array
    {
        $results = [];
        foreach ($this->indexData() as $key => $value) {
            $date = $value[0];
            $client_id = 
            $client_type = $value[2];
            $operation = $value[3];
            $amount = $value[4];
            $currency = $value[5];            
            
            if ($value[3] == 'cash_in') {
                $total = (array)$this->cashIn((float)$amount, $currency);
                
            } elseif ($operation == 'cash_out' && $client_type == 'natural') {
                $total = (array)$this->cashOutNat((float)$amount, $currency);
            } elseif ($operation == 'cash_out' && $client_type == 'legal') {
                $total = (array)$this->cashOutLegal((float)$amount, $currency);
            } 
            self::$data[$key]['commission'] = $total;
            $results[] = $total;            
        }
        echo '<pre>';
        print_r(self::$data);
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

    // public function firstCashOutNat(float $amount, string $currency) : string
    // {
    //     $commissionRate = 0.3;
    //     $discountAmount = 1000.00;
        
    //     if ($currency == 'EUR' && $amount > $discountAmount) {
    //         $commission = ($amount - $discountAmount)/100 * $commissionRate;
    //     } elseif ($currency == 'USD' && $amount > $discountAmount*$this->rateUSD) {
    //         $commission = ($amount - $discountAmount*$this->rateUSD)/100 * $commissionRate;
    //     } elseif ($currency == 'JPY' && $amount > $discountAmount*$this->rateJPY) {
    //         $commission = ($amount - $discountAmount*$this->rateJPY)/100 * $commissionRate;
    //     }
    //     else {  
    //         $commission = 0;
    //     }

    //     return self::roundUp($commission, $currency);
    // }

    public function cashOutNat() //: string
    {
        $commissionRate = 0.3;
        $cashOutNatFees = $this->countCashOutNat();
        foreach ($cashOutNatFees as $keyy => $clientCashOuts) {
            $totalAmount = 0;   # total of client's cash outs in EUR
            $count = 1;         # number of client's cash outs
            $discountAmount = 1000.00;
            foreach ($clientCashOuts as $key => $value) {
                $date = Carbon::parse($value[0]);
                $week = $date->isoWeek();
                
                if ($value[5] == 'EUR') {
                    $totalAmount += $value[4];
                } elseif ($value[5] == 'USD') {
                    $totalAmount += $value[4]/$this->rateUSD;   #total in EUR
                } elseif ($value[5] == 'JPY') {
                    $totalAmount += $value[4]/$this->rateJPY;   #total in EUR
                }
                if ($count == 1) {
                    if ($totalAmount > $discountAmount) {
                        if ($value[5] == 'EUR') {
                            $commission = ($value[4] - $discountAmount)/100 * $commissionRate;
                        } elseif ($value[5] == 'USD') {
                            $commission = ($value[4] - $discountAmount*$this->rateUSD)/100 * $commissionRate;
                        } elseif ($value[5] == 'JPY') {
                            $commission = ($value[4] - $discountAmount*$this->rateJPY)/100 * $commissionRate;
                        }
                        $discountAmount = 0;
                    } elseif ($totalAmount <= $discountAmount) { 
                        $commission = 0;
                        if ($value[5] == 'EUR') {
                            $discountAmount -= $value[4];
                        } elseif ($value[5] == 'USD') {
                            $discountAmount -= $value[4]/$this->rateUSD;
                        } elseif ($value[5] == 'JPY') {
                            $discountAmount -= $value[4]/$this->rateJPY;
                        }
                    }
                    $count++;
                    $lastCashOutWeek = $week;
                    $lastDate = $date;
                    
                } elseif ($count > 1 && $count <= 3) {
                    if ($week == $lastCashOutWeek && $lastDate->diffInDays($date) < 7 && $totalAmount <= $discountAmount) {
                        $commission = 0;
                        if ($value[5] == 'EUR') {
                            $discountAmount -= $value[4];
                        } elseif ($value[5] == 'USD') {
                            $discountAmount -= $value[4]/$this->rateUSD;
                        } elseif ($value[5] == 'JPY') {
                            $discountAmount -= $value[4]/$this->rateJPY;
                        }
                    } elseif ($week == $lastCashOutWeek && $lastDate->diffInDays($date) < 7 && $totalAmount > $discountAmount) {                        
                        if ($value[5] == 'EUR') {
                            $commission = ($value[4] - $discountAmount)/100 * $commissionRate;
                        } elseif ($value[5] == 'USD') {
                            $commission = ($value[4] - $discountAmount*$this->rateUSD)/100 * $commissionRate;
                        } elseif ($value[5] == 'JPY') {
                            $commission = ($value[4] - $discountAmount*$this->rateJPY)/100 * $commissionRate;
                        }
                        $discountAmount = 0;                       
                    } elseif ($week != $lastCashOutWeek) {
                        $count = 1;
                        $discountAmount = 1000;
                        //... is naujo
                    }
                    $count++;
                    $lastCashOutWeek = $week;
                    $lastDate = $date;

                } else {
                    // the same week 4th+ cash out = full commission
                    $commission = $value[4] / 100 * $commissionRate;
                }               
                $total = self::roundUp($commission, $value[5]);
                echo $total. '<br>';
                $cashOutNatFees[$keyy][$key]['commission'] = $total;
            }
            // echo $totalAmount . '<br>';
        }
        echo '<pre>';
        print_r($cashOutNatFees);
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
            foreach ($this->indexData() as $key => $value) {
                if ($value[1] == $id && $value[3] == 'cash_out') {
                    $clientCashOuts[] = $value;
                }
            }
            $cashOuts[] = $clientCashOuts;
        }
        // echo '<pre>';
        // print_r($cashOuts);
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

        return number_format(ceil($value * $mult) / $mult, $precision, '.', '');        
    }


}