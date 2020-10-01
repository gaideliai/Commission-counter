<?php

require '..\vendor\autoload.php';

use Buysera\CommissionTask\CSVReader;
use Buysera\CommissionTask\Fees;
use Buysera\CommissionTask\CSVWriter;


$data = CSVReader::read('input.csv');
Fees::getData($data);
$fees= new Fees;
// $fees->commissionCalc();
$fees->countCashOutNat();
// CSVWriter::write($fees->commissionCalc());