<?php

namespace Domain\CleanCode\Services;

use Domain\CleanCode\Models\Product;
use Domain\CleanCode\Repositories\ProductRepository;

class ProductService
{
    public function __construct(protected ProductRepository $productRepository)
    {
        // ...
    }

    public function saveProduct(array $productData): ?Product
    {
        return $this->productRepository->create($productData);
    }

    public function makeProduct(array $productData): ?Product
    {
        return $this->productRepository->make($productData);
    }
}
