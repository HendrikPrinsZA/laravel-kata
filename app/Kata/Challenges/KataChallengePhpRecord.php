<?php

namespace App\Kata\Challenges;

class KataChallengePhpRecord extends KataChallengePhp
{
    public function loopWithCondition(int $limit): float
    {
        $output = $limit;
        $items = range(1, $limit * 10);
        foreach ($items as $item) {
            $output += $output / $item;
        }

        return $output;
    }

    public function replaceString(int $limit): float
    {
        $text = str_repeat('abc', $limit);
        $text = str_replace(
            str_repeat('abc', $limit),
            str_repeat('def', $limit),
            $text
        );

        return floatval(md5($text));
    }
}
