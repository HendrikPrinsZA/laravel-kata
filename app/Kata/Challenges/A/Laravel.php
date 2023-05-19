<?php

namespace App\Kata\Challenges\A;

use App\Enums\CountryCode;
use App\Kata\KataChallenge;
use App\Models\Country;

class Laravel extends KataChallenge
{
    protected const MAX_INTERATIONS = 100;

    public function modelMutationVersusServiceSingle(int $limit): float
    {
        $country = $this->getCountryByIndex($limit);
        $country->setExchangeRatesAggregates();

        $value = array_sum([
            $country->exchangeRatesAvg,
            $country->exchangeRatesSum,
            $country->exchangeRatesMin,
            $country->exchangeRatesMax,
            $country->exchangeRatesCount,
        ]);

        return $this->return($value);
    }

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
