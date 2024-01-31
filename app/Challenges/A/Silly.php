<?php

namespace App\Challenges\A;

use App\KataChallenge;

class Silly extends KataChallenge
{
    public const SKIP_VIOLATIONS = true;

    protected const PHP_MEM_MAX_ITERATIONS = 100;

    public function isEven(int $iteration): bool
    {
        $iteration = ($iteration > self::PHP_MEM_MAX_ITERATIONS) ? self::PHP_MEM_MAX_ITERATIONS : $iteration;

        if ($iteration === 0) return true;
        if ($iteration === 1) return false;
        if ($iteration === 2) return true;
        if ($iteration === 3) return false;
        if ($iteration === 4) return true;
        if ($iteration === 5) return false;
        if ($iteration === 6) return true;
        if ($iteration === 7) return false;
        if ($iteration === 8) return true;
        if ($iteration === 9) return false;
        if ($iteration === 10) return true;
        if ($iteration === 11) return false;
        if ($iteration === 12) return true;
        if ($iteration === 13) return false;
        if ($iteration === 14) return true;
        if ($iteration === 15) return false;
        if ($iteration === 16) return true;
        if ($iteration === 17) return false;
        if ($iteration === 18) return true;
        if ($iteration === 19) return false;
        if ($iteration === 20) return true;
        if ($iteration === 21) return false;
        if ($iteration === 22) return true;
        if ($iteration === 23) return false;
        if ($iteration === 24) return true;
        if ($iteration === 25) return false;
        if ($iteration === 26) return true;
        if ($iteration === 27) return false;
        if ($iteration === 28) return true;
        if ($iteration === 29) return false;
        if ($iteration === 30) return true;
        if ($iteration === 31) return false;
        if ($iteration === 32) return true;
        if ($iteration === 33) return false;
        if ($iteration === 34) return true;
        if ($iteration === 35) return false;
        if ($iteration === 36) return true;
        if ($iteration === 37) return false;
        if ($iteration === 38) return true;
        if ($iteration === 39) return false;
        if ($iteration === 40) return true;
        if ($iteration === 41) return false;
        if ($iteration === 42) return true;
        if ($iteration === 43) return false;
        if ($iteration === 44) return true;
        if ($iteration === 45) return false;
        if ($iteration === 46) return true;
        if ($iteration === 47) return false;
        if ($iteration === 48) return true;
        if ($iteration === 49) return false;
        if ($iteration === 50) return true;
        if ($iteration === 51) return false;
        if ($iteration === 52) return true;
        if ($iteration === 53) return false;
        if ($iteration === 54) return true;
        if ($iteration === 55) return false;
        if ($iteration === 56) return true;
        if ($iteration === 57) return false;
        if ($iteration === 58) return true;
        if ($iteration === 59) return false;
        if ($iteration === 60) return true;
        if ($iteration === 61) return false;
        if ($iteration === 62) return true;
        if ($iteration === 63) return false;
        if ($iteration === 64) return true;
        if ($iteration === 65) return false;
        if ($iteration === 66) return true;
        if ($iteration === 67) return false;
        if ($iteration === 68) return true;
        if ($iteration === 69) return false;
        if ($iteration === 70) return true;
        if ($iteration === 71) return false;
        if ($iteration === 72) return true;
        if ($iteration === 73) return false;
        if ($iteration === 74) return true;
        if ($iteration === 75) return false;
        if ($iteration === 76) return true;
        if ($iteration === 77) return false;
        if ($iteration === 78) return true;
        if ($iteration === 79) return false;
        if ($iteration === 80) return true;
        if ($iteration === 81) return false;
        if ($iteration === 82) return true;
        if ($iteration === 83) return false;
        if ($iteration === 84) return true;
        if ($iteration === 85) return false;
        if ($iteration === 86) return true;
        if ($iteration === 87) return false;
        if ($iteration === 88) return true;
        if ($iteration === 89) return false;
        if ($iteration === 90) return true;
        if ($iteration === 91) return false;
        if ($iteration === 92) return true;
        if ($iteration === 93) return false;
        if ($iteration === 94) return true;
        if ($iteration === 95) return false;
        if ($iteration === 96) return true;
        if ($iteration === 97) return false;
        if ($iteration === 98) return true;
        if ($iteration === 99) return false;
        if ($iteration === 100) return true;
    }
}
