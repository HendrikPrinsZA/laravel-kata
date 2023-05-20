<?php

use App\Kata\KataRunner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

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
 * A teapot for Jason
 */
Route::any('/', function (Request $request) {
    $message = "I'm a teapot!";

    return response($message, 418);
});

/**
 * Check the app health
 */
Route::get('health', function (Request $request) {
    return JsonResource::make([
        'success' => true,
    ]);
});

/**
 * Get the list of challenges
 */
Route::get('kata', function (Request $request) {
    return JsonResource::make([
        'success' => true,
        'data' => collect(config('laravel-kata.challenges', []))
            ->map(function ($className) {
                $classNameParts = explode('\\', $className);

                return array_pop($classNameParts);
            })
            ->toArray(),
    ]);
});

/**
 * Get the list of challenge's methods
 */
Route::get('kata/{challenge}', function (Request $request, string $challenge) {
    $class = sprintf(
        'App\\Kata\\Challenges\\A\\%s',
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

    $data = collect($reflectionClass->getMethods())
        ->filter(fn (ReflectionMethod $method) => $method->class === $class)
        ->filter(fn (ReflectionMethod $method) => $method->isPublic())
        ->map(fn ($method) => $method->name)
        ->toArray();

    return JsonResource::make([
        'success' => true,
        'data' => $data,
    ]);
});

/**
 * Run the challenge
 */
Route::get('kata/{challenge}/run', function (Request $request, string $challenge) {
    /** @var KataRunner $kataRunner */
    $kataRunner = app()->makeWith(KataRunner::class, [
        'command' => null,
        'challenges' => [
            sprintf('App\\Kata\\Challenges\\A\\%s', $challenge),
        ],
    ]);

    $data = $kataRunner->run();

    return JsonResource::make([
        'success' => true,
        'data' => $data,
    ]);
});
