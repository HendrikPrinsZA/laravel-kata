<?php

namespace Database\Seeders\Models;

use App\Services\ExchangeRateService;

class ExchangeRatesSeeder extends ModelSeeder
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {
    }

    public function seed(): void
    {
        $this->exchangeRateService->syncExchangeRates();
    }
}
