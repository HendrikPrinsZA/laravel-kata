<?php

namespace Tests\Command;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class KataTestCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_console_command(): void
    {
        $this->artisan('kata:test')
            ->expectsOutputToContain('Database: laravel')
            ->expectsOutputToContain('Database: testing')
            ->assertExitCode(0);
    }
}
