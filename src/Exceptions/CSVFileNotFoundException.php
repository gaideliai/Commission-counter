<?php

namespace Buysera\CommissionTask\Exceptions;

class CSVFileNotFoundException extends \Exception 
{
    function __construct($path) 
    {
        $msg = "File $path not found";
        parent::__construct($msg);
    }
}