<?php

require '..\vendor\autoload.php';

use Buysera\CommissionTask\CSVReader;
use Buysera\CommissionTask\Fees;

$data = CSVReader::read('input.csv');
Fees::getData($data);
// $fees= new Fees;
// echo $fees->cashOutNat();