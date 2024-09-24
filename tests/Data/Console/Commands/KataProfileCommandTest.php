<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('laravel-kata.gains-perc-minimum', 0);
    Config::set('laravel-kata.max-seconds', 0);
    Config::set('laravel-kata.max-iterations', 1);
    Config::set('laravel-kata.progress-bar-disabled', true);
    Config::set('laravel-kata.save-results-to-storage', false);
    Config::set('laravel-kata.dummy-data.max-users', 1);
    Config::set('laravel-kata.dummy-data.max-user-blogs', 1);
    Config::set('laravel-kata.show-hints', true);
    Config::set('laravel-kata.show-hints-extended', true);
    Config::set('laravel-kata.show-code-snippets', true);
});

it('can run single', function () {
    $this->artisan('kata:profile Sample calculatePi')
        ->expectsConfirmation('Ready to run App\Challenges\A\Sample->calculatePi(1)?', 'Yes')
        ->expectsConfirmation('Ready to run App\Challenges\B\Sample->calculatePi(1)?', 'Yes')
        ->assertExitCode(Command::SUCCESS);
});

it('can run single (no/s)', function () {
    $this->artisan('kata:profile Sample calculatePi')
        ->expectsConfirmation('Ready to run App\Challenges\A\Sample->calculatePi(1)?', 'No')
        ->expectsConfirmation('Ready to run App\Challenges\B\Sample->calculatePi(1)?', 'No')
        ->assertExitCode(Command::SUCCESS);
});
