<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;

class KataChallengePhp extends KataChallenge
{
    protected int $maxIterations = 500;

    public function baseline(): void { }

    /**
     * Never Use Count or Any Other Methods in The Condition Section of a Loop
     *
     * See https://www.codeclouds.com/blog/php-profiling-performance-optimization/
     */
    public function loopWithCondition(int $limit): float
    {
        $output = $limit;
        $items = range(1, $limit);
        for ($x = 0; $x < count($items); $x++) {
            $output += $output / $items[$x];
        }
        return $output;
    }

    public function loopWhile(int $limit): float
    {
        $output = $limit;
        $counter = $limit * 10;
        while ($counter > 0) {
            $output += $output / $counter;
            $counter--;
        }
        return $output;
    }

    // https://www.site24x7.com/blog/a-developers-guide-to-optimizing-php-performance
    public function replaceString(int $limit): float
    {
        $text = str_repeat('abc', $limit);

        $text = preg_replace([
            "/a/",
            "/b/",
            "/c/",
        ], [
            'd',
            'e',
            'f'
        ], $text);

        return floatval(md5($text));
    }
}
