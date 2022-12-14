<?php

use Illuminate\Database\MySqlConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
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
Route::get('/health', function (Request $request) {
    // try {
    //     /** @var MySqlConnection $connection */
    //     $connection = DB::connection();
    //     $connection->getPDO();
    //     $connection->getDatabaseName();
    // } catch (Exception $exception) {
    //     return JsonResource::make([
    //         'success' => false,
    //         'message' => 'Unable to connect to MySQL',
    //         'error' => $exception->getMessage(),
    //     ]);
    // }

    // try {
    //     Redis::connection()->ping();
    // } catch (Exception $exception) {
    //     return JsonResource::make([
    //         'success' => false,
    //         'message' => 'Unable to connect to Redis',
    //         'error' => $exception->getMessage(),
    //     ]);
    // }

    return JsonResource::make([
        'success' => true,
    ]);
});

/**
 * Get the list of challenges
 */
Route::get('/kata', function (Request $request) {
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
Route::get('/kata/{challenge}', function (Request $request, string $challenge) {
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

    $data = collect($reflectionClass->getMethods())
        ->filter(fn (ReflectionMethod $method) => $method->class === $class)
        ->filter(fn (ReflectionMethod $method) => $method->isPublic())
        ->filter(fn (ReflectionMethod $method) => $method->name !== 'baseline')
        ->map(fn ($method) => $method->name)
        ->toArray();

    return JsonResource::make([
        'success' => true,
        'data' => $data,
    ]);
});

/**
 * Hit the challenge's method
 */
Route::get('/kata/{challenge}/{method}', function (Request $request, string $challenge, string $method) {
    $data = [
        'challenge' => $challenge,
        'method' => $method,
    ];

    $className = sprintf(
        'App\\Kata\\Challenges\\%s',
        $challenge
    );

    $instance = app($className, [
        'request' => $request,
    ]);

    $data = [];
    $iterations = $request->get('iterations', 1);
    foreach (range(1, $iterations) as $iteration) {
        $data[] = $instance->{$method}($iteration);
    }

    return JsonResource::make([
        'success' => true,
        'data' => $data,
    ]);
});
