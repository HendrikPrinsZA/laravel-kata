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
        return $success
            ? sprintf('<fg=green>%s</>', $string)
            : sprintf('<fg=red>%s</>', $string);

        $el = $success
            ? 'info'
            : 'warn';

        return sprintf('<%s>%s</%s>', $el, $string, $el);
    }
}
