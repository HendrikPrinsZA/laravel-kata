<?php

namespace Domain\CleanCode\Objects;

class ShapeSquare extends Shape
{
    public function __construct(
        private float $sideLength
    ) {
        // ...
    }

    public function area(): float
    {
        return pow($this->sideLength, 2);
    }

    public function circumference(): float
    {
        return 4 * $this->sideLength;
    }
}
