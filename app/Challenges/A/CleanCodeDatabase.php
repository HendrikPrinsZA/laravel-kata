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

    public function productsCreate(int $limit): float
    {
        $limit = $limit > 100 ? 100 : $limit;

        /** @var \Domain\CleanCode\Controllers\ProductController $productController */
        $productController = app()->make(ProductController::class);

        $sum = 0;
        for ($i = 0; $i <= $limit; $i++) {
            $price = 10.99 * $i;
            $product = $productController->createProduct([
                'name' => 'Test product '.$i,
                'description' => 'Test product description',
                'price' => $price,
            ]);

            $sum += $product->price;
        }

        return $this->return($sum);
    }
}
