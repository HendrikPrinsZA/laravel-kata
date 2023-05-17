<?php

namespace App\Kata\Challenges;

class KataChallengePhpRecord extends KataChallengePhp
{
    public function nativeRange(int $limit): int
    {
        return count(range(0, $this->getRangeLimit($limit)));
    }

    public function nativeSum(int $limit): int
    {
        $numbers = range(0, $this->getRangeLimit($limit));

        return array_sum($numbers);
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
