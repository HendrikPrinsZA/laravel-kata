<?php

namespace App\Challenges\A;

use App\Enums\CountryCode;
use App\KataChallenge;
use App\Models\Country;

class Laravel extends KataChallenge
{
    protected const MAX_INTERATIONS = 100;

    public function modelMutationVersusServiceMultiple(): float
    {
        $total = 0;
        foreach (Country::all() as $country) {
            $country->setExchangeRatesAggregates();
            $total += array_sum([
                $country->exchangeRatesAvg,
                $country->exchangeRatesSum,
                $country->exchangeRatesMin,
                $country->exchangeRatesMax,
                $country->exchangeRatesCount,
            ]);
        }

        return $this->return($total);
    }

    protected function getCountryByIndex(int $limit): Country
    {
        $countryCodes = CountryCode::cases();
        $index = $limit % count($countryCodes);

        return Country::firstWhere('code', $countryCodes[$index]->value);
    }
}
