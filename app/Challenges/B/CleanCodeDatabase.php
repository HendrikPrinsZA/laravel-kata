<?php

namespace App\Challenges\B;

use App\Challenges\A\CleanCodeDatabase as ACleanCodeDatabase;

use function Domain\DirtyCode\product_create;

class CleanCodeDatabase extends ACleanCodeDatabase
{
    public function productsCreate(int $limit): float
    {
        $limit = $limit > 100 ? 100 : $limit;

        $sum = 0;
        for ($i = 0; $i <= $limit; $i++) {
            $price = 10.99 * $i;
            $product = product_create([
                'name' => 'Test product '.$i,
                'description' => 'Test product description',
                'price' => $price,
            ]);

            $sum += $product->price;
        }

        return $this->return($sum);
    }
}
