<?php

use App\Http\Controllers\v1\AppController;
use App\Http\Controllers\v1\MeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('/', function () {
    return ['Laravel' => app()->version()];
});


Route::group(['prefix' => 'app'], function () {
    Route::get('/', [AppController::class, 'index']);
    Route::get('/{id}', [AppController::class, 'show']);
    Route::post('/', [AppController::class, 'store']);
    Route::delete('/{id}', [AppController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| Secure routes
|--------------------------------------------------------------------------
|
| Routes for authenticated users
|
*/
Route::middleware(['auth.rosalana'])->group(function () {
    Route::get('/me', [MeController::class, 'me']);
});

require __DIR__ . '/auth.php';
