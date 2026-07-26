<?php

if (! function_exists('indian_number')) {
    /**
     * Format a number using the Indian numbering system.
     * e.g. 1234567 → 12,34,567
     *
     * @param  int|float|string $number
     * @param  int              $decimals
     * @return string
     */
    function indian_number($number, int $decimals = 0): string
    {
        $number = (string) round((float) $number, $decimals);

        // Split into integer and decimal parts
        [$intPart, $decPart] = array_pad(explode('.', $number, 2), 2, '');

        // Handle negative sign
        $sign = '';
        if (str_starts_with($intPart, '-')) {
            $sign    = '-';
            $intPart = substr($intPart, 1);
        }

        // Indian system: last 3 digits, then groups of 2
        $len = strlen($intPart);
        if ($len <= 3) {
            $formatted = $intPart;
        } else {
            // The rightmost 3 digits are kept together
            $last3     = substr($intPart, -3);
            $remaining = substr($intPart, 0, $len - 3);

            // Split the remaining digits into groups of 2 (from right)
            $chunks    = [];
            while (strlen($remaining) > 0) {
                $chunks[] = substr($remaining, -2);
                $remaining = substr($remaining, 0, strlen($remaining) - 2);
            }

            $formatted = implode(',', array_reverse($chunks)) . ',' . $last3;
        }

        $result = $sign . $formatted;

        if ($decimals > 0) {
            $result .= '.' . str_pad($decPart, $decimals, '0');
        }

        return $result;
    }
}
