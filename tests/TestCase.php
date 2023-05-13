<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

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
