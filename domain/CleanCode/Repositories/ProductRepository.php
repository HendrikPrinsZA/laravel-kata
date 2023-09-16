<?php

namespace Domain\CleanCode\Repositories;

use Domain\CleanCode\Models\Product;

class ProductRepository extends Repository
{
    public function __construct(Product $product)
    {
        parent::__construct($product);
    }
}
