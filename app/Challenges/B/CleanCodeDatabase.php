<?php

namespace App\Challenges\B;

use App\Challenges\A\CleanCodeDatabase as ACleanCodeDatabase;

use function Domain\DirtyCode\product_create;

class CleanCodeDatabase extends ACleanCodeDatabase
{
    public function productsCreate(int $iteration): float
    {
        $iteration = $iteration > 100 ? 100 : $iteration;

        $sum = 0;
        for ($i = 0; $i <= $iteration; $i++) {
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

    public function memoryAllocation(int $iteration): array
    {
        $largeArray = range(1, $iteration);
        $resultArray = [];

        foreach ($largeArray as $item) {
            $resultArray[] = strrev(str_repeat($item, 100));
        }

        return $this->return($resultArray);
    }
}
