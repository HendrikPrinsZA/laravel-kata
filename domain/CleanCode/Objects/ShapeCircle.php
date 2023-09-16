<?php

namespace Domain\CleanCode\Objects;

class ShapeCircle extends Shape
{
    public function __construct(private float $radius)
    {
        // ...
    }

    public function area(): float
    {
        return pi() * $this->radius ** 2;
    }

    public function circumference(): float
    {
        return 2 * pi() * $this->radius;
    }
}
