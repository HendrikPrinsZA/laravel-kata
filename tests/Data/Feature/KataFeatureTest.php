<?php

namespace Tests\Data\Feature;

it('can connect', function () {
    $this->get('/')->assertStatus(200);
    $this->assertTrue(true);
});

test('parent', function () {
    $response = $this->get('/api/kata')
        ->assertStatus(200);

    $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenges']);
    $this->assertTrue(true);

    return array_values($response->json('data'));
});

test('child', function (array $challenges) {
    $challengeMethods = [];

    foreach ($challenges as $challenge) {
        $response = $this->get(sprintf('/api/kata/%s', $challenge))
            ->assertStatus(200);

        $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenge']);

        $challengeMethods[$challenge] = $response->json('data');
    }

    return $challengeMethods;
})->depends('parent');
