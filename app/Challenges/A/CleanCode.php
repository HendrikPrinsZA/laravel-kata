<?php

namespace App\Challenges\A;

use App\KataChallenge;
use Domain\CleanCode\Controllers\ProductController;
use Domain\CleanCode\Objects\ShapeCircle;
use Domain\CleanCode\Objects\ShapeRectangle;
use Domain\CleanCode\Objects\ShapeSquare;

class CleanCode extends KataChallenge
{
    public function productsMake(int $iteration): float
    {
        $productController = app()->make(ProductController::class);

        $sum = 0;
        for ($i = 0; $i <= $iteration; $i++) {
            $price = 10.99 * $i;
            $product = $productController->makeProduct([
                'name' => 'Test product '.$i,
                'description' => 'Test product description',
                'price' => $price,
            ]);

            $sum += $product->price;
        }

        return $this->return($sum);
    }

    public function shapes(int $iteration): float
    {
        $sum = 0;
        for ($i = 0; $i <= $iteration; $i++) {
            $shapeCircle = new ShapeCircle(5.0 * ($i + 1));
            $shapeSquare = new ShapeSquare(4.0 * ($i + 1));
            $shapeRectangle = new ShapeRectangle(3.0 * ($i + 1), 7.0 * ($i + 1));

            $sum +=
                $shapeCircle->areaPlusCircumference() +
                $shapeSquare->areaPlusCircumference() +
                $shapeRectangle->areaPlusCircumference();
        }

        return $this->return($sum);
    }
}
