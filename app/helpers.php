<?php

if (! function_exists('rs')) {
    /** Whole rupees as "Rs 12,500". Negative amounts show as "(Rs 500)", the accounting way. */
    function rs(int|float|null $amount): string
    {
        $amount = (int) round((float) $amount);

        return $amount < 0 ? '(Rs '.number_format(-$amount).')' : 'Rs '.number_format($amount);
    }
}
