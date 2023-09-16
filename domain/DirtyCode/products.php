<?php

namespace Domain\DirtyCode;

use Domain\CleanCode\Models\Product;
use Exception;

function product_create(array $productData): ?Product
{
    if (empty($productData['name'])) {
        throw new Exception('Expected property "name" cannot be empty.');
    }

    if (empty($productData['description'])) {
        throw new Exception('Expected property "description" cannot be empty.');
    }

    if (! is_numeric($productData['price'])) {
        throw new Exception('Expected property "price" must be numeric.');
    }

    return Product::create($productData);
}

function product_make(array $productData): ?Product
{
    if (empty($productData['name'])) {
        throw new Exception('Expected property "name" cannot be empty.');
    }

    if (empty($productData['description'])) {
        throw new Exception('Expected property "description" cannot be empty.');
    }

    if (! is_numeric($productData['price'])) {
        throw new Exception('Expected property "price" must be numeric.');
    }

    return Product::make($productData);
}
