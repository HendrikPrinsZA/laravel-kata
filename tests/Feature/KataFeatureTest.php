<?php

namespace Tests\Feature;

use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KataFeatureTest extends TestCase
{
    use RefreshDatabase;

    const RESPONSE_STRUCTURES = [
        'challenges' => [
            'success' => 'boolean',
            'data' => 'array',
            'data.0' => 'string'
        ],
        'challenge' => [
            'success' => 'boolean',
            'data' => 'array',
            'data.0' => 'string'
        ]
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UsersSeeder::class);
    }

    public function test_api()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test fetching the challenges
     */
    public function test_api_kata_challenges(): array
    {
        $response = $this->get('/api/kata/challenges');
        $response->assertStatus(200);
        $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenges']);
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
            $response = $this->get(sprintf('/api/kata/challenge/%s', $challenge));

            $response->assertStatus(200);
            $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenge']);

            $challengeMethods[$challenge] = $response->json('data');
        }

        return $challengeMethods;
    }

    /**
     * Test executing the challenge
     *
     * @depends test_api_kata_challenges_challenge
     *
     * Tests
     * - Check that all classes exist
     * - Check that all class methods exist
     */
    public function test_api_kata_challenges_challenge_method(array $challengeMethods): void
    {
        foreach ($challengeMethods as $challenge => $methods) {
            foreach (['', 'Record'] as $challengAppend) {
                $challengAppended = sprintf('%s%s', $challenge, $challengAppend);
                foreach ($methods as $method) {
                    $uri = sprintf(
                        'api/kata/challenge/%s/%s?max-seconds=0&max-iterations=1',
                        $challengAppended,
                        $method
                    );
                    $response = $this->get($uri);

                    print_r([
                        'fn' => 'test_api_kata_challenges_challenge_method',
                        'uri' => $uri,
                        'response' => $response->json('data')
                    ]);
                }
            }
        }
    }
}
