<?php

namespace Domain\CleanCode\Controllers;

use Domain\CleanCode\Exceptions\InvalidProductDataException;
use Domain\CleanCode\Models\Product;
use Domain\CleanCode\Services\ProductService;

class ProductController
{
    public function __construct(protected ProductService $productService)
    {
        // ...
    }

    public function createProduct(array $productData): ?Product
    {
        $this->validateProductData($productData);

        return $this->productService->saveProduct($productData);
    }

    public function makeProduct(array $productData): ?Product
    {
        $this->validateProductData($productData);

        return $this->productService->makeProduct($productData);
    }

    private function validateProductData(array $productData): void
    {
        if (empty($productData['name'])) {
            throw new InvalidProductDataException('Expected property "name" cannot be empty.');
        }

        if (empty($productData['description'])) {
            throw new InvalidProductDataException('Expected property "description" cannot be empty.');
        }

        if (! is_numeric($productData['price'])) {
            throw new InvalidProductDataException('Expected property "price" must be numeric.');
        }
    }
}
