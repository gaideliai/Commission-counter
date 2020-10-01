<?php

declare(strict_types=1);

namespace Buysera\CommissionTask;

class CSVWriter
{
    public static function write(array $list) : void
    {
        $file = fopen("output.csv", "w");

        foreach ($list as $line) {
            fputcsv($file, $line);
        }
        
        fclose($file);
    }
}