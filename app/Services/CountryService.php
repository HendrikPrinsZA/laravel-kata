<?php

namespace App\Services;

use App\Models\Country;

class CountryService
{
    public function getExchangeRatesAggregates(Country $country): array
    {
        return [
            'avg' => (float) $country->exchangeRates()->avg('rate'),
            'sum' => (float) $country->exchangeRates()->sum('rate'),
            'min' => (float) $country->exchangeRates()->min('rate'),
            'max' => (float) $country->exchangeRates()->max('rate'),
            'count' => (int) $country->exchangeRates()->count(),
        ];
    }
}
