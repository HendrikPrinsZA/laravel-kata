<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

final class KataFeatureTest extends TestCase
{
    use DatabaseMigrations;

    const RESPONSE_STRUCTURES = [
        'challenges' => [
            'success' => 'boolean',
            'data' => 'array',
            'data.0.challenge' => 'string',
            'data.0.methods' => 'array',
        ],
        'challenge' => [
            'success' => 'boolean',
            'data' => 'array',
            'data.0.challenge' => 'string',
            'data.0.methods' => 'array',
        ],
    ];

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
        $response = $this->get('/api/kata');
        $response->assertStatus(200);
        $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenges']);

        return collect($response->json('data'))->pluck('challenge')->toArray();
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
            $response = $this->get(sprintf('/api/kata/%s', $challenge));

            $response->assertStatus(200);
            $this->assertJsonResponseFormat($response, self::RESPONSE_STRUCTURES['challenge']);

            $challengeMethods[$challenge] = collect($response->json('data'))->first()['methods'];
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
        $this->seed(UsersSeeder::class);
        $this->assertNotNull(User::first(), 'Expected users, but none found');

        foreach ($challengeMethods as $challenge => $methods) {
            $challengeRecord = sprintf('%sRecord', $challenge);

            foreach ($methods as $method) {
                $uri = sprintf(
                    'api/kata/%s/%s?max-seconds=0&max-iterations=1',
                    $challenge,
                    $method
                );
                $dataBase = $this->get($uri)->json('data');

                // Call the Record
                $uri = sprintf(
                    'api/kata/%s/%s?max-seconds=0&max-iterations=1',
                    $challengeRecord,
                    $method
                );
                $dataRecord = $this->get($uri)->json('data');

                $this->assertEquals($dataBase, $dataRecord, sprintf(
                    'Output from %s::%s does not match with %s::%s',
                    $challengeRecord,
                    $method,
                    $challenge,
                    $method
                ));
            }
        }
    }
}
