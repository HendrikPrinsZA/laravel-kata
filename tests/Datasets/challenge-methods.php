<?php

use App\Challenges\A\CleanCode;
use App\Challenges\A\CleanCodeDatabase;
use App\Challenges\A\Eloquent;
use App\Challenges\A\Laravel;
use App\Challenges\A\MySql;
use App\Challenges\A\Php;
use App\Challenges\A\Sample;
use App\KataChallenge;

$challengeClasses = [
    Sample::class,
    Php::class,
    Eloquent::class,
    MySql::class,
    Laravel::class,
    CleanCode::class,
    CleanCodeDatabase::class,
];

$challengeMethods = [];
foreach ($challengeClasses as $challengeClass) {
    $kataChallengeReflection = new ReflectionClass($challengeClass);

    /** @var ReflectionMethod $reflectionMethod */
    foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
        if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
            continue;
        }

        if ($reflectionMethod->class === KataChallenge::class) {
            continue;
        }

        $challengeMethods[] = [
            $challengeClass,
            $reflectionMethod->name,
        ];
    }
}

dataset('challenge-methods', $challengeMethods);
