<?php

namespace Tests\Feature;

use Tests\TestCase;

final class KataFeatureTest extends TestCase
{
    const RESPONSE_STRUCTURES = [
        'challenges' => [
            'success' => 'boolean',
            'data' => 'array',
            'data.0' => 'string',
        ],
        'challenge' => [
            'success' => 'boolean',
            'data' => 'array',
            'data.0' => 'string',
        ],
    ];

    public function test_api()
    {
        $this->get('/')
            ->assertStatus(200);

        $this->assertTrue(true);
    }

    /**
     * Test fetching the challenges
     */
    public function test_api_kata_challenges(): array
    {
        $response = $this->get('/api/kata')
            ->assertStatus(200);

        $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenges']);
        $this->assertTrue(true);

        return array_values($response->json('data'));
    }

    /**
     * Test fetching the challenges + methods
     *
     * @depends test_api_kata_challenges
     */
    public function test_api_kata_challenges_challenge(array $challenges): array
    {
        $challengeMethods = [];

        foreach ($challenges as $challenge) {
            $response = $this->get(sprintf('/api/kata/%s', $challenge))
                ->assertStatus(200);

            $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenge']);

            $challengeMethods[$challenge] = $response->json('data');
        }

        return $challengeMethods;
    }
}
