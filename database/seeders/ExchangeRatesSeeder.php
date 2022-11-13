<?php

namespace Database\Seeders;

use App\Services\ExchangeRateService;

class ExchangeRatesSeeder extends BaseSeeder
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
