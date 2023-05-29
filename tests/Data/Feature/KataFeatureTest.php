<?php

namespace Tests\Data\Feature;

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
