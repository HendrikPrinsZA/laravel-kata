<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function assertJsonResponseFormat(TestResponse $response, array $responseFormat): void
    {
        $response->assertJson(
            function (AssertableJson $json) use ($responseFormat) {
                return $json->whereAllType($responseFormat);
            }
        );
    }
}
