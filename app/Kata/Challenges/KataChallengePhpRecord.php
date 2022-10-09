<?php

namespace App\Kata\Challenges;

class KataChallengePhpRecord extends KataChallengePhp
{
    public function loopWithCondition(int $limit): float
    {
        $output = $limit;
        $items = range(1, $limit);
        foreach ($items as $item) {
            $output += $output / $item;
        }
        return $output;
    }

    public function replaceString(int $limit): float
    {
        $text = str_repeat('abc', $limit);
        $text = str_replace('abc', 'def', $text);
        return floatval(md5($text));
    }

}
