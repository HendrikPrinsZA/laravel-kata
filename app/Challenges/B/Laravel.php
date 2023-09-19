<?php

namespace App\Challenges\B;

use App\Challenges\A\Laravel as ALaravel;
use App\Models\Country;
use App\Services\CountryService;

class Laravel extends ALaravel
{
    public function modelMutationVersusServiceMultiple(): float
    {
        /** @var CountryService $countryService */
        $countryService = app()->make(CountryService::class);

        $total = 0;
        foreach (Country::all() as $country) {
            $total += array_sum($countryService->getExchangeRatesAggregates($country));
        }

        return $total;
    }
}
