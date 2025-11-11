<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Format tanggal dengan aman.
     * Jika value kosong atau "EMPTY", akan mengembalikan string kosong.
     */
    public static function safeDateFormat($value)
    {
        if (is_null($value) || trim($value) === '' || strtoupper($value) === 'EMPTY') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d F Y');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format angka dengan aman.
     * Jika value kosong atau bukan angka, akan mengembalikan string kosong.
     */
    public static function safeNumber($value)
    {
        if (is_null($value) || trim($value) === '' || strtoupper($value) === 'EMPTY' || !is_numeric($value)) {
            return '';
        }

        return number_format((float) $value, 2, '.', ',');
    }
}
