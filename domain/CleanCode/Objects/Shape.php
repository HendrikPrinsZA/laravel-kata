<?php

namespace Domain\CleanCode\Objects;

use Domain\CleanCode\Contracts\ShapeInterface;

abstract class Shape implements ShapeInterface
{
    public function areaPlusCircumference(): float
    {
        return $this->area() + $this->circumference();
    }
}
