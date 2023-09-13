<?php

namespace Tests\Data\Feature;

use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('laravel-kata.gains-perc-minimum', -100);
    Config::set('laravel-kata.max-seconds', 0);
    Config::set('laravel-kata.max-iterations', 1);
    Config::set('laravel-kata.progress-bar-disabled', true);
    Config::set('laravel-kata.save-results-to-storage', false);
    Config::set('laravel-kata.dummy-data.max-users', 1);
    Config::set('laravel-kata.dummy-data.max-user-blogs', 1);
    Config::set('laravel-kata.show-hints', false);
    Config::set('laravel-kata.show-hints-extended', false);
});

it('can connect', function () {
    $this->get('/')->assertStatus(200);
    $this->assertTrue(true);
});

test('get challenges', function () {
    $response = $this->get('/api/kata')
        ->assertStatus(200);

    $this->assertJsonResponseFormat($response, [
        'success' => 'boolean',
        'data' => 'array',
        'data.0' => 'string',
    ]);

    return array_values($response->json('data'));
});

test('get challenge methods', function (array $challenges) {
    $challengeMethods = [];

    foreach ($challenges as $challenge) {
        $response = $this->get(sprintf('/api/kata/%s', $challenge))
            ->assertStatus(200);

        $this->assertJsonResponseFormat($response, [
            'success' => 'boolean',
            'data' => 'array',
            'data.0' => 'string',
        ]);

        $challengeMethods[$challenge] = $response->json('data');
    }

    return $challengeMethods;
})->depends('get challenges');

test('run challenge', function () {
    $response = $this->get('/api/kata/Sample/run', [
        'iterations' => 1,
    ])->assertStatus(200);

    $this->assertJsonResponseFormat($response, [
        'success' => 'boolean',
        'data' => 'array',
        'data.report' => 'array',
    ]);
});
