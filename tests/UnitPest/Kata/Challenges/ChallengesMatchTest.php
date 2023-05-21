<?php

use App\Kata\KataChallenge;

function scopeChalengesMatchTest(array $challenges, int $iterations)
{
    foreach ($challenges as $challengeA) {
        $kataChallengeReflection = new ReflectionClass($challengeA);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            if ($reflectionMethod->class === KataChallenge::class) {
                continue;
            }

            $instanceA = app()->make($reflectionMethod->class);
            $returnA = $instanceA->{$reflectionMethod->name}($iterations);

            $challengeB = str_replace('\\A\\', '\\B\\', $challengeA);
            $instanceB = app()->make($challengeB);
            $returnB = $instanceB->{$reflectionMethod->name}($iterations);

            expect($returnA)
                ->toEqual($returnB);
        }
    }
}

it('has valid return (A) - 1', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 1);
});

it('has valid return (B) - 1', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 1);
});

it('has valid return (A) - 10', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 10);
});

it('has valid return (B) - 10', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 10);
});

it('has valid return (A) - 100', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 100);
});

it('has valid return (B) - 100', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 100);
});

it('has valid return (A) - 1000', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 1000);
});

it('has valid return (B) - 1000', function () {
    $challenges = config('laravel-kata.challenges');
    scopeChalengesMatchTest($challenges, 1000);
});
