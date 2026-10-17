<?php

namespace App\Support;

class Bytes
{
    /** 1536 => "1.5 KB". Plain PHP, so it does not need the intl extension. */
    public static function format(int|float $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        if ($i === 0) {
            return (int) $bytes.' B';
        }

        return number_format($bytes, $bytes >= 100 ? 0 : $precision).' '.$units[$i];
    }
}
