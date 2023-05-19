<?php

namespace App\Kata\Challenges\B;

use App\Kata\Challenges\A\Laravel as ALaravel;
use App\Models\Country;
use App\Services\CountryService;

class Laravel extends ALaravel
{
    public function modelMutationVersusServiceSingle(int $limit): float
    {
        /** @var CountryService $countryService */
        $countryService = app()->make(CountryService::class);
        $country = $this->getCountryByIndex($limit);

        $value = array_sum($countryService->getExchangeRatesAggregates($country));

        return $this->return($value);
    }

    public function modelMutationVersusServiceMultiple(int $limit): float
    {
        /** @var CountryService $countryService */
        $countryService = app()->make(CountryService::class);

        $total = 0;
        foreach (Country::all() as $country) {
            $total += array_sum($countryService->getExchangeRatesAggregates($country));
        }

        return $this->return($total);
    }
}
