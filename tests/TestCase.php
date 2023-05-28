<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
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

    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );
    }

    protected function assertJsonResponseFormat(TestResponse $response, array $responseFormat): void
    {
        $response->assertJson(
            function (AssertableJson $json) use ($responseFormat) {
                return $json->whereAllType($responseFormat);
            }
        );
    }
}
