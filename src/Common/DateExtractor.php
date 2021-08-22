<?php

namespace Common;

use Carbon\Carbon;

class DateExtractor
{
    public static function extractFromString($string): ?Carbon
    {
        preg_match('/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}/', $string, $matches);
        if (count($matches) == 0) {
            return null;
        }

        $date_parts = explode('/', $matches[0]);

        $year = $date_parts[2];
        $month = $date_parts[1];
        $day = $date_parts[0];

        if (strlen($year) == 2) {
            $year = '20'.$year;
        }

        return Carbon::create($year, $month, $day);
    }
}