<?php

namespace App\Challenges\A;

use App\KataChallenge;
use Domain\CleanCode\Controllers\ProductController;
use Domain\CleanCode\Models\Product;

class CleanCodeDatabase extends KataChallenge
{
    protected const MAX_INTERATIONS = 150;

    public function beforeProductsCreate(): void
    {
        Product::truncate();
    }

    public function productsCreate(int $iteration): float
    {
        $iteration = $iteration > 100 ? 100 : $iteration;

        /** @var \Domain\CleanCode\Controllers\ProductController $productController */
        $productController = app()->make(ProductController::class);

        $sum = 0;
        for ($i = 0; $i <= $iteration; $i++) {
            $price = 10.99 * $i;
            $product = $productController->createProduct([
                'name' => 'Test product '.$i,
                'description' => 'Test product description',
                'price' => $price,
            ]);

            $sum += $product->price;
        }

        return $sum;
    }

    public function memoryAllocation(int $iteration): array
    {
        $largeArray = range(1, $iteration);
        $tempArray = [];

        foreach ($largeArray as $item) {
            $tempArray[] = str_repeat($item, 100);
        }

        $resultArray = [];
        foreach ($tempArray as $item) {
            $resultArray[] = strrev($item);
        }

        return $resultArray;
    }
}
