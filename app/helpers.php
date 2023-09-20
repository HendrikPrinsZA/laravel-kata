<?php

if (! function_exists('help_me_code')) {
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

if (! function_exists('array_subset_by_keys')) {
    /**
     * Returns a subset of the array by keys
     */
    function array_subset_by_keys(array $array, array $keys): array
    {
        $return = [];

        foreach ($keys as $key) {
            $return[$key] = $array[$key] ?? null;
        }

        return $return;
    }
}

if (! function_exists('wrap_in_format')) {
    /**
     * Wrap in format for CLI
     */
    function wrap_in_format(string $string, bool $success, bool $warn = false): string
    {
        return $success
            ? sprintf('<fg=green>%s</>', $string)
            : sprintf('<fg=%s>%s</>', $warn ? 'yellow' : 'red', $string);
    }
}

if (! function_exists('bytes_to_human')) {
    function bytes_to_human(float $bytes): string
    {
        $bytes = intval($bytes);
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf(
            '%s %s',
            round($bytes / (1024 ** $factor)),
            $units[$factor]
        );
    }
}

if (! function_exists('time_to_human')) {
    function time_to_human(float $milliseconds, bool $digital = true): string
    {
        return $digital ? ms_to_time($milliseconds) : sprintf('%s s', number_format($milliseconds, 9));
    }
}

if (! function_exists('ms_to_time')) {
    function ms_to_time(float $milliseconds)
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        $minutes = $minutes - ($hours * 60);
        $seconds = $seconds - ($hours * 60 * 60) - ($minutes * 60);
        $ms = $milliseconds % 1000;

        $timeFormat = sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $ms);
        if ($timeFormat === '00:00:00.000') {
            return time_to_human($milliseconds, false);
        }

        return $timeFormat;
    }
}
