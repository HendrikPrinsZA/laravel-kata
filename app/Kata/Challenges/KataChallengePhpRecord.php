<?php

namespace App\Kata\Challenges;

class KataChallengePhpRecord extends KataChallengePhp
{
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
