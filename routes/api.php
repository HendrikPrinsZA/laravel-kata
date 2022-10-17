<?php

use App\Kata\KataChallenge\KataChallengeSample;
use App\Http\Controllers\KataController;
use App\Http\Resources\KataRunResponseResource;
use App\Kata\KataChallenge;
use App\Kata\KataRunner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Console\Output\BufferedOutput;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Get the list of challenges
 */
Route::get('/kata/challenges', function (Request $request) {
    return JsonResource::make([
        'success' => true,
        'data' => collect(config('laravel-kata.challenges', []))
            ->map(function ($className) {
                $classNameParts = explode('\\', $className);
                return array_pop($classNameParts);
            })
            ->toArray()
    ]);
});

/**
 * Get the list of challenge's methods
 */
Route::get('/kata/challenge/{challenge}', function (Request $request, string $challenge) {
    $class = sprintf(
        'App\\Kata\\Challenges\\%s',
        $challenge
    );

    $reflectionClass = new ReflectionClass($class);
    $data = collect($reflectionClass->getMethods())
        ->filter(fn(ReflectionMethod $method) => $method->class === $class)
        ->filter(fn(ReflectionMethod $method) => $method->isPublic())
        ->filter(fn(ReflectionMethod $method) => $method->name !== 'baseline')
        ->map(fn($method) => $method->name)
        ->toArray();

    return JsonResource::make([
        'success' => true,
        'data' => $data
    ]);
});

/**
 * Hit the challenge's method
 */
Route::get('/kata/challenge/{challenge}/{method}', function (Request $request, string $challenge, string $method) {
    $data = [
        'challenge' => $challenge,
        'method' => $method,
    ];

    $className = sprintf(
        'App\\Kata\\Challenges\\%s',
        $challenge
    );

    $instance = app($className);
    $data = $instance->{$method}(1);

    return JsonResource::make([
        'success' => true,
        'data' => $data
    ]);
});

Route::get('/kata', function (Request $request) {
    $maxIterations = $request->get('max-iterations', 100);

    /** @var KataRunner $kataRunner */
    $kataRunner = app(KataRunner::class, [
        'command' => null,
        'maxIterations' => $maxIterations,
        'mode' => 'all'
    ]);

    $results = $kataRunner->run();

    return JsonResource::make($results);
});

Route::get('/kata/before', function (Request $request) {
    $maxIterations = $request->get('max-iterations', 100);

    /** @var KataRunner $kataRunner */
    $kataRunner = app(KataRunner::class, [
        'command' => null,
        'maxIterations' => $maxIterations,
        'mode' => 'before'
    ]);

    $results = $kataRunner->run();

    return JsonResource::make($results);
});

Route::get('/kata/after', function (Request $request) {
    $maxIterations = $request->get('max-iterations', 100);

    // sleep(1);

    /** @var KataRunner $kataRunner */
    $kataRunner = app(KataRunner::class, [
        'command' => null,
        'maxIterations' => $maxIterations,
        'mode' => 'after'
    ]);

    $results = $kataRunner->run();

    return JsonResource::make($results);
});
