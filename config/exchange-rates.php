<?php

return [
    'starting-date' => '2010-01-01',
    'api-host' => env('EXCHANGE_RATE_API_HOST', sprintf('%s/mock/exchangerate', env('APP_URL'))),
    'api-key' => env('EXCHANGE_RATE_API_KEY', 'api-key'),
];
