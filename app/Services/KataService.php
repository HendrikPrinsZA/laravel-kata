<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class KataService
{
    public function getChallenges(): Collection
    {
        return collect(config('laravel-kata.challenges', []))
            ->map(function ($className) {
                $classNameParts = explode('\\', $className);

                return array_pop($classNameParts);
            });
    }

    public function getChallengeMethods(string $challenge): Collection
    {
        $class = sprintf(
            'App\\Kata\\Challenges\\%s',
            $challenge
        );

        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException $exception) {
            throw new Exception(sprintf(
                'Something bad happened: %s',
                $exception->getMessage()
            ));
        }

        return collect($reflectionClass->getMethods())
            ->filter(fn (ReflectionMethod $method) => $method->class === $class)
            ->filter(fn (ReflectionMethod $method) => $method->isPublic())
            ->filter(fn (ReflectionMethod $method) => $method->name !== 'baseline')
            ->map(fn ($method) => $method->name)
            ->values();
    }

    public function runChallengeMethod(Request $request, string $challenge, string $method): array
    {
        $className = sprintf(
            'App\\Kata\\Challenges\\%s',
            $challenge
        );

        $instance = app($className, [
            'request' => $request,
        ]);

        $outputs = collect();
        $iterations = $request->get('iterations', 1);
        foreach (range(1, $iterations) as $iteration) {
            $output = $instance->{$method}($iteration);
            $outputs->push(json_encode($output));
        }

        $json = $outputs->toJson();

        return [
            'outputs_md5' => md5($json),
            'outputs_last_10' => $outputs->take(-10),
            'outputs' => $json,
        ];
    }
}
