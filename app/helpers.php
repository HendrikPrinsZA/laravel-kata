<?php

if (!function_exists('help_me_code')) {
    /**
     * Parse absolute file path to editor path/hyperlink
     */
    function help_me_code(ReflectionMethod $reflectionMethod): string
    {
        return sprintf(
            '%s:%d',
            str_replace('/var/www/html/', '', $reflectionMethod->getFileName()),
            $reflectionMethod->getStartLine()
        );
    }
}

if (!function_exists('percentage_difference')) {
    /**
     * Calculates the percentage difference between two values.
     *
     * Exceptions
     *   1. If any number is null return null as the difference is not applicable
     *   2. If the first number is 0, the difference could be: -100 || 100 || 0
     */
    function percentage_difference(mixed $first, mixed $second, int $precision = 2): ?float
    {
        if (is_null($first) || is_null($second)) {
            return null;
        }

        $diff = empty($first)
            ? ($second <=> $first) * 100
            : (($second * 100) / $first) - 100;

        return round($diff, $precision);
    }
}

if (!function_exists('percentage_difference_fixed')) {
    /**
     * Calculates the percentage of value by min and max
     */
    function percentage_difference_fixed(float $min, float $max, float $value): float
    {
        $tempMax = $max - $min;
        $tempValue = $value - $min;

        if ($tempMax === 0.0) {
            $tempMax = 0.00000001;
        }

        if ($tempValue === 0.0) {
            $tempValue = 0.00000001;
        }

        return $tempValue / $tempMax;
    }
}

if (!function_exists('percentage_difference_baseline')) {
    /**
     * Calculates the percentage of value by percentage of baseline
     */
    function percentage_difference_baseline(float $baseline, float $value, bool $inverse = false): float
    {
        if ($baseline === 0.0) {
            $baseline = 0.00000001;
        }

        if ($value === 0.0) {
            $value = 0.00000001;
        }

        if ($inverse) {
            return $baseline / $value;
        }

        return $value / $baseline;
    }
}

if (!function_exists('percentage_change')) {
    /**
     * Calculates the percentage of old vs new
     */
    function percentage_change(float $oldValue, float $newValue, bool $inverse = false): float
    {
        if ($inverse) {
            $tempValue = $oldValue;
            $oldValue = $newValue;
            $newValue = $tempValue;
        }

        if ($oldValue === 0.0) {
            $oldValue = 0.00000001;
        }

        if ($newValue === 0.0) {
            $newValue = 0.00000001;
        }

        return (1 - $oldValue / $newValue);
    }
}
