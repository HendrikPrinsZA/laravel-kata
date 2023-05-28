<?php

use App\Enums\CountryCode;

dataset('country-exchange-rates', [
    'South Africa' => [
        CountryCode::ZA,
        [
            'avg' => 18.2757869,
            'sum' => 6688.938,
            'min' => 16.381,
            'max' => 21.228,
            'count' => 366,
        ],
    ],
    'Netherlands' => [
        CountryCode::NL,
        [
            'avg' => 6.0079638,
            'sum' => 8795.659,
            'min' => 0.836,
            'max' => 21.228,
            'count' => 1464,
        ],
    ],
]);
