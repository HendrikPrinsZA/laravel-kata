<?php

namespace App\Utilities;

use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Renderer\RendererConstant;

final class DiffUtility
{
    protected const DIFF_OPTIONS = [
        // Differ::CONTEXT_ALL can be used to show the whole file
        'context' => 1,
        // ignore case difference
        'ignoreCase' => false,
        // ignore whitespace difference
        'ignoreWhitespace' => false,
    ];

    protected const RENDERER_OPTIONS = [
        // how detailed the rendered HTML is? (none, line, word, char)
        'detailLevel' => 'line',
        // renderer language: eng, cht, chs, jpn, ...
        // or an array which has the same keys with a language file
        'language' => 'eng',
        // show line numbers in HTML renderers
        'lineNumbers' => true,
        // show a separator between different diff hunks in HTML renderers
        'separateBlock' => true,
        // show the (table) header
        'showHeader' => true,
        // convert spaces/tabs into HTML codes like `<span class="ch sp"> </span>`
        // and the frontend is responsible for rendering them with CSS.
        // when using this, "spacesToNbsp" should be false and "tabSize" is not respected.
        'spaceToHtmlTag' => false,
        // the frontend HTML could use CSS "white-space: pre;" to visualize consecutive whitespaces
        // but if you want to visualize them in the backend with "&nbsp;", you can set this to true
        'spacesToNbsp' => false,
        // HTML renderer tab width (negative = do not convert into spaces)
        'tabSize' => 4,
        // this option is currently only for the Combined renderer.
        // it determines whether a replace-type block should be merged or not
        // depending on the content changed ratio, which values between 0 and 1.
        'mergeThreshold' => 0.8,
        // this option is currently only for the Unified and the Context renderers.
        // RendererConstant::CLI_COLOR_AUTO = colorize the output if possible (default)
        // RendererConstant::CLI_COLOR_ENABLE = force to colorize the output
        // RendererConstant::CLI_COLOR_DISABLE = force not to colorize the output
        'cliColorization' => RendererConstant::CLI_COLOR_AUTO,
        // this option is currently only for the Json renderer.
        // internally, ops (tags) are all int type but this is not good for human reading.
        // set this to "true" to convert them into string form before outputting.
        'outputTagAsString' => false,
        // this option is currently only for the Json renderer.
        // it controls how the output JSON is formatted.
        // see available options on https://www.php.net/manual/en/function.json-encode.php
        'jsonEncodeFlags' => \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
        // this option is currently effective when the "detailLevel" is "word"
        // characters listed in this array can be used to make diff segments into a whole
        // for example, making "<del>good</del>-<del>looking</del>" into "<del>good-looking</del>"
        // this should bring better readability but set this to empty array if you do not want it
        'wordGlues' => [' ', '-'],
        // change this value to a string as the returned diff if the two input strings are identical
        'resultForIdenticals' => null,
        // extra HTML classes added to the DOM of the diff container
        'wrapperClasses' => ['diff-wrapper'],
    ];

    public static function friendlyDiff(mixed $before, mixed $after): string
    {
        $before = is_array($before) ? $before[0] : $before;
        $after = is_array($after) ? $after[0] : $before;

        $before = is_string($before) ? $before : json_encode($before, JSON_PRETTY_PRINT);
        $after = is_string($after) ? $after : json_encode($after, JSON_PRETTY_PRINT);
        $result = DiffHelper::calculate(
            $before,
            $after,
            'Unified',
            self::DIFF_OPTIONS,
            self::RENDERER_OPTIONS
        );

        return $result;
    }
}
