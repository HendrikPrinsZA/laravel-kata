<?php

namespace Tests\Command;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_console_command(): void
    {
        $this->artisan('kata:test')
            ->expectsOutputToContain('Database: laravel')
            ->expectsOutputToContain('Database: testing')
            ->assertExitCode(0);
    }
}
