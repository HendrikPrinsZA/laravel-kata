<?php

namespace Domain\CleanCode\Objects;

class ShapeRectangle extends Shape
{
    public function __construct(
        private float $width,
        private float $height
    ) {
        // ...
    }

    public function area(): float
    {
        return $this->width * $this->height;
    }

    public function circumference(): float
    {
        return 2 * ($this->width + $this->height);
    }
}
