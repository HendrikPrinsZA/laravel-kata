<?php

namespace App\Kata\Challenges;

use App\Models\Country;
use App\Services\CountryService;

class KataChallengeLaravelRecord extends KataChallengeLaravel
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
