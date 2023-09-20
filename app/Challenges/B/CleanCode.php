<?php

namespace App\Challenges\B;

use App\Challenges\A\CleanCode as ACleanCode;

use function Domain\DirtyCode\circle_area_plus_circumference;
use function Domain\DirtyCode\product_make;
use function Domain\DirtyCode\rectangle_area_plus_circumference;
use function Domain\DirtyCode\square_area_plus_circumference;

class CleanCode extends ACleanCode
{
    public function productsMake(int $iteration): float
    {
        $sum = 0;

        for ($i = 0; $i <= $iteration; $i++) {
            $price = 10.99 * $i;
            $product = product_make([
                'name' => 'Test product '.$i,
                'description' => 'Test product description',
                'price' => $price,
            ]);

            $sum += $product->price;
        }

        return $sum;
    }

    public function shapes(int $iteration): float
    {
        $sum = 0;
        for ($i = 0; $i <= $iteration; $i++) {
            $sum +=
                circle_area_plus_circumference(5.0 * ($i + 1)) +
                square_area_plus_circumference(4.0 * ($i + 1)) +
                rectangle_area_plus_circumference(3.0 * ($i + 1), 7.0 * ($i + 1));
        }

        return $sum;
    }
}
