<?php

use App\Exceptions\KataChallengeException;
use App\Kata\Exceptions\KataChallengeNotFoundException;
use App\Models\Blog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Tests\Data\Console\Commands\FakeChallenges\A\NotFound;
use Tests\Data\Console\Commands\FakeChallenges\A\TooSlow;
use Tests\Data\Console\Commands\FakeChallenges\A\WrongOutput;

beforeEach(function () {
    Config::set('laravel-kata.gains-perc-minimum', 0);
    Config::set('laravel-kata.max-seconds', 0);
    Config::set('laravel-kata.max-iterations', 1);
    Config::set('laravel-kata.progress-bar-disabled', true);
    Config::set('laravel-kata.save-results-to-storage', false);
    Config::set('laravel-kata.dummy-data.max-users', 1);
    Config::set('laravel-kata.dummy-data.max-user-blogs', 1);
    Config::set('laravel-kata.show-hints', false);
    Config::set('laravel-kata.show-hints-extended', false);
});

it('can run all', function () {
    $this->artisan('kata:run --all')
        ->assertExitCode(Command::SUCCESS);
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

it('fails if A not found', function () {
    Config::set('laravel-kata.challenges', [
        'Not\\A\\Class',
    ]);

    $this->expectException(KataChallengeNotFoundException::class);
    $this->artisan('kata:run --all');
});

it('fails if B not found', function () {
    Config::set('laravel-kata.challenges', [
        NotFound::class,
    ]);

    $this->expectException(KataChallengeNotFoundException::class);
    $this->artisan('kata:run --all');
});

it('fails on challenge that does not exist', function () {
    $this->expectException(KataChallengeNotFoundException::class);
    $this->artisan('kata:run --challenge=ClassDoesNotExist');
});

it('fails if challenge not in config', function () {
    Config::set('laravel-kata.challenges', [
        'Not\\A\\Class',
    ]);

    $this->expectException(KataChallengeNotFoundException::class);
    $this->artisan('kata:run --challenge=Sample');
});

it('fails on expected model expty', function () {
    Config::set('laravel-kata.challenges', [
        NotFound::class,
    ]);
    Blog::truncate();

    $this->expectException(KataChallengeException::class);
    $this->artisan('kata:run --all');
});
