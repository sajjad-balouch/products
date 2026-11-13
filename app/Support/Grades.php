<?php

namespace App\Support;

class Grades
{
    public static function for(float $percent): string
    {
        return match (true) {
            $percent >= 90 => 'A+',
            $percent >= 80 => 'A',
            $percent >= 70 => 'B',
            $percent >= 60 => 'C',
            $percent >= 50 => 'D',
            $percent >= 33 => 'E',
            default => 'F',
        };
    }
}
