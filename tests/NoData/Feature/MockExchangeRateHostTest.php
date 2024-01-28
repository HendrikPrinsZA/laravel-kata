<?php

it('has env set', function () {
    expect(config('exchange-rates.api-host'))
        ->toBe('http://localhost/mock/exchangerate');
});

it('can get timeframe', function () {
    $response = $this->get('/mock/exchangerate/timeframe?access_key=fake-key&start_date=2024-01-28&end_date=2024-01-28&source=EUR&currencies=AED,GBP,USD,ZAR');
    expect($response)->toMatchSnapshot();
});
