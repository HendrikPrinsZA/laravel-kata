<?php

use App\Http\Controllers\Api\GainsController;
use App\Http\Controllers\Api\KataController;
use Illuminate\Database\MySqlConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Router;
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
    $data = [
        'api' => true,
        'database' => false,
        'cache' => false,
    ];
    $errors = [];
    $suggestions = [];

    try {
        /** @var MySqlConnection $connection */
        $connection = DB::connection();
        $connection->getPDO();
        $connection->getDatabaseName();
        $data['database'] = true;
    } catch (Exception $exception) {
        $errors[] = sprintf(
            'Unable to connect to MySQL: %s',
            $exception->getMessage()
        );
        $suggestions[] = 'Run the following command `sail down && sail up -d && npm run reset`';
        $suggestions[] = sprintf(
            'How to test: `docker exec -it kata-mysql mysql -u%s -p%s -e"SELECT User, Host FROM mysql.user;"`',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password')
        );
    }

    try {
        Redis::connection()->ping();
        $data['cache'] = true;
    } catch (Exception $exception) {
        $errors[] = sprintf(
            'Unable to connect to Redis: %s',
            $exception->getMessage()
        );
        $suggestions[] = 'Run the following command `sail down && sail up -d && npm run reset`';
    }

    return JsonResource::make([
        'success' => true, // empty($errors), // Not ready for this yet...
        'data' => $data,
        'errors' => $errors,
        'suggestions' => $suggestions,
    ]);
});

Route::group([
    'prefix' => 'kata',
    'namespace' => 'Api',
], function (Router $router) {
    $router->get('/', [KataController::class, 'index']);
    $router->get('/{challenge}', fn (Request $request, string $challenge) => app(KataController::class, [
        'request' => $request,
        'challenge' => $challenge,
    ])->index()
    );
    $router->get('/{challenge}/{method}', fn (Request $request, string $challenge, string $method) => app(KataController::class, [
        'request' => $request,
        'challenge' => $challenge,
        'method' => $method,
    ])->index()
    );
});

/**
 * Get the latest gains report data
 *
 * Note: This is only a workaround for the CORS issue when
 *       fetching http://localhost/storage/gains/KataChallengeSample-calculatePi.json
 */
Route::group([
    'prefix' => 'gains',
    'namespace' => 'Api',
], function (Router $router) {
    $router->get('/', [GainsController::class, 'index']);
    $router->get('/{challenge}', fn (Request $request, string $challenge) => app(GainsController::class, [
        'request' => $request,
        'challenge' => $challenge,
    ])->index()
    );
    $router->get('/{challenge}/{method}', fn (Request $request, string $challenge, string $method) => app(GainsController::class, [
        'request' => $request,
        'challenge' => $challenge,
        'method' => $method,
    ])->index()
    );
});
