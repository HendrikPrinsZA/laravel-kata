<?php

namespace App\Enums;

enum KataRunnerPhase: string
{
    case A = 'a';
    case B = 'b';

    public function getLabel(): string
    {
        return match ($this) {
            self::A => 'A: Slow',
            self::B => 'B: Fast',
        };
    }
}
