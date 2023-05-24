<?php

use App\Exceptions\KataChallengeException;
use App\Kata\Exceptions\KataChallengeBNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Tests\UnitPest\Commands\FakeChallenges\A\NotFoundB;
use Tests\UnitPest\Commands\FakeChallenges\A\TooSlow;
use Tests\UnitPest\Commands\FakeChallenges\A\WrongOutput;

beforeEach(function () {
    Config::set('laravel-kata.max-seconds', 0);
    Config::set('laravel-kata.max-iterations', 1);
    Config::set('laravel-kata.progress-bar-disabled', true);
    Config::set('laravel-kata.save-results-to-storage', false);
    Config::set('laravel-kata.dummy-data.max-users', 1);
    Config::set('laravel-kata.dummy-data.max-user-blogs', 1);
});

it('can run all', function () {
    $this->artisan('kata:run --all')
        ->assertExitCode(Command::SUCCESS); // Maybe we don't care about the exit code, consitency?
});

it('can run single', function () {
    $this->artisan('kata:run')
        ->expectsQuestion('Challenges', '#0')
        ->expectsQuestion('Challenges (1)', 'n')
        ->assertExitCode(Command::SUCCESS);
});

it('can run by challenge', function () {
    $this->artisan('kata:run --challenge=Sample')
        ->assertExitCode(Command::SUCCESS);
});

it('fails on challenge that does not exist', function () {
    $this->expectException(KataChallengeException::class);
    $this->artisan('kata:run --challenge=ClassDoesNotExist');
});

it('fails on challenge not in config', function () {
    Config::set('laravel-kata.challenges', [
        WrongOutput::class,
    ]);

    $this->expectException(KataChallengeException::class);
    $this->artisan('kata:run --challenge=Sample');
});

it('fails on wrong output', function () {
    Config::set('laravel-kata.challenges', [
        WrongOutput::class,
    ]);

    $this->artisan('kata:run --all')
        ->expectsOutputToContain('Outputs does not match')
        ->assertExitCode(Command::FAILURE);
});

it('fails on too slow', function () {
    Config::set('laravel-kata.challenges', [
        TooSlow::class,
    ]);

    $this->artisan('kata:run --all')
        ->expectsOutputToContain('Score is lower than expected')
        ->assertExitCode(Command::FAILURE);
});

it('fails if B not found', function () {
    Config::set('laravel-kata.challenges', [
        NotFoundB::class,
    ]);

    $this->expectException(KataChallengeBNotFoundException::class);
    $this->artisan('kata:run --all');
});
