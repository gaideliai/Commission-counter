<?php

declare(strict_types=1);

namespace Buysera\CommissionTask;

use Buysera\CommissionTask\Exceptions\CSVFileNotFoundException;


class CSVReader
{
    public static function read(string $path): array
    {
        if (!file_exists(__DIR__.'/'.$path)) {
            throw new CSVFileNotFoundException($path);
        } else {
            return $csv = array_map('str_getcsv', file(__DIR__.'/'.$path));
        }

    }
}