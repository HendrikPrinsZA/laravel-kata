<?php

namespace Domain\DirtyCode;

function circle_area_plus_circumference(float $radius): float
{
    return
        (pi() * $radius ** 2) + // area
        (2 * pi() * $radius);   // circumference
}

function square_area_plus_circumference(float $sideLength): float
{
    return
        pow($sideLength, 2) + // area
        (4 * $sideLength);    // circumference
}

function rectangle_area_plus_circumference(float $width, float $height): float
{
    return
        ($width * $height) + // area
        (2 * ($width + $height)); // circumference
}
