<?php

use Illuminate\Support\Carbon;

if (! function_exists('format_currency')) {
    /**
     * Format an amount as Kenyan Shillings.
     */
    function format_currency(float|int|string|null $amount): string
    {
        return 'KES '.number_format((float) $amount, 2);
    }
}

if (! function_exists('format_date')) {
    /**
     * Format a date in the application's display format.
     */
    function format_date(Carbon|string|null $date): string
    {
        if ($date === null) {
            return '—';
        }

        return Carbon::parse($date)->format('d M Y');
    }
}
