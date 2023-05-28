<?php

use Illuminate\Support\Facades\Cache;

function scopeCacheTestGet(string $key, mixed $value = 'default'): string
{
    if (Cache::has($key)) {
        return Cache::get($key);
    }

    Cache::set($key, $value);

    return $value;
}

it('can do caching', function () {
    $key = 'sample-key-1';
    expect(Cache::has($key))->toBeFalse();

    $value = sprintf('Value set at %s', now()->toDateTimeString());
    $valueInitial = scopeCacheTestGet($key, $value);

    expect($valueInitial)
        ->toBe($value);

    $valueCached = scopeCacheTestGet($key);

    expect($valueCached)
        ->toBe($value);
});
