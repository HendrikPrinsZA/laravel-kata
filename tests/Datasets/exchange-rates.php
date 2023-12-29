<?php

use App\Enums\CountryCode;

dataset('country-exchange-rates', [
    'South Africa' => [
        CountryCode::ZA,
        [
            'avg' => 18.286123,
            'sum' => 6692.721,
            'min' => 16.39,
            'max' => 21.273,
            'count' => 366,
        ],
    ],
    'Netherlands' => [
        CountryCode::NL,
        [
            'avg' => 6.0111223,
            'sum' => 8800.283,
            'min' => 0.836,
            'max' => 21.273,
            'count' => 1464,
        ],
    ],
]);
