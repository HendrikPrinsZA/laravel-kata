<?php

namespace App\Kata\Challenges;

class KataChallengePhpRecord extends KataChallengePhp
{
    public function nativeRange(int $limit): array
    {
        return range(0, $this->getRangeLimit($limit));
    }

    public function nativeSum(int $limit): int
    {
        return array_sum($this->nativeRange($limit));
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
