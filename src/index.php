<?php

require '..\vendor\autoload.php';

$data = CSVReader::read('input.csv');
Fees::getData($data);