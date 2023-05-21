<?php

use App\Kata\KataChallenge;

function scopeChalengesTest(array $challenges, string $ab, int $iterations)
{
    foreach ($challenges as $challenge) {
        $challenge = str_replace(
            '\\A\\',
            sprintf('\\%s\\', $ab),
            $challenge
        );

        $kataChallengeReflection = new ReflectionClass($challenge);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            if ($reflectionMethod->class === KataChallenge::class) {
                continue;
            }

            $instance = app()->make($reflectionMethod->class);
            $return = $instance->{$reflectionMethod->name}($iterations);

            expect($return)
                ->not->toBeEmpty(sprintf(
                    '%s::%s',
                    collect(explode('\\', $reflectionMethod->class))->take(-2)->join(':'),
                    $reflectionMethod->name,
                ));
        }
    }
}

it('has valid return (A) - 1', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'A', 1);
});

it('has valid return (B) - 1', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'B', 1);
});

it('has valid return (A) - 10', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'A', 10);
});

it('has valid return (B) - 10', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'B', 10);
});

it('has valid return (A) - 100', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'A', 100);
});

it('has valid return (B) - 100', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'B', 100);
});

it('has valid return (A) - 1000', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'A', 1000);
});

it('has valid return (B) - 1000', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesTest($challenges, 'B', 1000);
});
