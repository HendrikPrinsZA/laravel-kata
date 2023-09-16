<?php

use App\Exceptions\KataChallengeException;
use App\Exceptions\KataChallengeNotFoundException;
use App\Exceptions\KataChallengeProfilingException;
use App\Exceptions\KataChallengeScoreException;
use App\Models\Blog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Tests\Data\Console\Commands\FakeChallenges\A\NotFound;
use Tests\Data\Console\Commands\FakeChallenges\A\NotUsingReturn;
use Tests\Data\Console\Commands\FakeChallenges\A\TooSlow;
use Tests\Data\Console\Commands\FakeChallenges\A\WrongOutput;

beforeEach(function () {
    Config::set('laravel-kata.gains-perc-minimum', -100);
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

it('can run by challenge method', function () {
    $this->artisan('kata:run --challenge=Sample --method=calculatePi')
        ->assertExitCode(Command::SUCCESS);
});

it('fails on wrong output', function () {
    Config::set('laravel-kata.challenges', [
        WrongOutput::class,
    ]);

    $this->artisan('kata:run --all');
})->throws(KataChallengeScoreException::class);

it('fails on too slow', function () {
    Config::set('laravel-kata.gains-perc-minimum', 0);
    Config::set('laravel-kata.challenges', [
        TooSlow::class,
    ]);

    $this->artisan('kata:run --all');
})->throws(KataChallengeScoreException::class);

it('fails if A not found', function () {
    Config::set('laravel-kata.challenges', [
        'Not\\A\\Class',
    ]);

    $this->artisan('kata:run --all');
})->throws(KataChallengeNotFoundException::class);

it('fails if B not found', function () {
    Config::set('laravel-kata.challenges', [
        NotFound::class,
    ]);

    $this->artisan('kata:run --all');
})->throws(KataChallengeNotFoundException::class);

it('fails on challenge that does not exist', function () {
    $this->artisan('kata:run --challenge=ClassDoesNotExist');
})->throws(KataChallengeNotFoundException::class);

it('fails if challenge not in config', function () {
    Config::set('laravel-kata.challenges', [
        'Not\\A\\Class',
    ]);

    $this->artisan('kata:run --challenge=Sample');
})->throws(KataChallengeNotFoundException::class);

it('fails on expected model empty', function () {
    Config::set('laravel-kata.challenges', [
        NotFound::class,
    ]);
    Blog::truncate();

    $this->artisan('kata:run --all');
})->throws(KataChallengeException::class);

it('fails when not using $this->return()', function () {
    Config::set('laravel-kata.challenges', [
        NotUsingReturn::class,
    ]);

    $this->artisan('kata:run --all');
})->throws(KataChallengeProfilingException::class);
