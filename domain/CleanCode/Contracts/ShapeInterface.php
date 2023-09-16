<?php

namespace Domain\CleanCode\Contracts;

interface ShapeInterface
{
    public function area(): float;

    public function circumference(): float;

    public function areaPlusCircumference(): float;
}
