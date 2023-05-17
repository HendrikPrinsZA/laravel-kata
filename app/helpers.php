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
    function wrap_in_format(string $string, bool $success): string
    {
        return $success ? sprintf('<fg=green>%s</>', $string) : sprintf('<fg=red>%s</>', $string);
    }
}

if (! function_exists('bytes_to_human')) {
    function bytes_to_human(int $bytes): string
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = floor(log($bytes, 1024));
        $power = pow(1024, $i);
        $size = round($bytes / $power, 2);

        return sprintf('%d %s', $size, $units[$i]);
    }
}

if (! function_exists('time_to_human')) {
    function time_to_human(float $seconds): string
    {
        $millisecs = floor(($seconds - floor($seconds)) * 10000000000);

        return sprintf('%d ms', $millisecs);

        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);
        $millisecs = floor(($seconds - floor($seconds)) * 10000000000);

        return sprintf('%02d:%02d:%02d.%010d', $hours, $mins, $secs, $millisecs);
    }
}
