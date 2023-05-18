<?php

namespace Database\Seeders\Models;

use App\Services\ExchangeRateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ExchangeRatesSeeder extends ModelSeeder
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {
    }

    public function seed(): void
    {
        if (app()->environment('testing')) {
            Carbon::setTestNow(Carbon::parse('2022-05-17'));
            $this->loadFxCache();
            $this->exchangeRateService->syncExchangeRates();
            Carbon::setTestNow();

            return;
        }

        $this->exchangeRateService->syncExchangeRates();
    }

    protected function loadFxCache(): void
    {
        $pattern = sprintf('%s/Files/fx_*.json', __DIR__);

        foreach (glob($pattern) as $path) {
            $key = str_replace(
                ['_', '.json'],
                [':', ''],
                basename($path)
            );
            Cache::set($key, json_decode(file_get_contents($path), true));
        }
    }

    protected function saveFxCache(): void
    {
        $pattern = sprintf('%s/Files/fx_*.json', __DIR__);
        foreach (glob($pattern) as $path) {
            unlink($path);
        }

        $redis = Cache::connection('cache');
        $keys = $redis->keys('cache:fx:*');
        foreach ($keys as $key) {
            $key = str_replace('default_cache:', '', $key);
            $path = sprintf(
                '%s/Files/%s.json',
                __DIR__,
                str_replace(':', '_', $key)
            );

            file_put_contents($path, json_encode(Cache::get($key)));
        }
    }
}
