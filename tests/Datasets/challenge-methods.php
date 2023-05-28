<?php

use App\Kata\Challenges\A\Eloquent;
use App\Kata\Challenges\A\Laravel;
use App\Kata\Challenges\A\MySql;
use App\Kata\Challenges\A\Php;
use App\Kata\Challenges\A\Sample;
use App\Kata\KataChallenge;

$challengeClasses = [
    Sample::class,
    Php::class,
    Eloquent::class,
    MySql::class,
    Laravel::class,
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
