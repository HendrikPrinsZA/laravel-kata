<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

it('can run command', function () {
    $this->artisan('kata:test')
        ->expectsOutputToContain('Database: laravel')
        ->expectsOutputToContain('Database: testing')
        ->assertExitCode(Command::SUCCESS);
});

it('can run command and fail', function (string $database) {
    $configKey = sprintf('database.connections.%s.database', $database);
    Config::set($configKey, 'fake-database');

    $this->artisan('kata:test')
        ->assertExitCode(Command::FAILURE);
})->with([
    'mysql',
    'testing',
]);
