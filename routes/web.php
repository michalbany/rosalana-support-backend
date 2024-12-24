<?php

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


/*
|--------------------------------------------------------------------------
| Secure routes
|--------------------------------------------------------------------------
|
| Routes for authenticated users
|
*/
Route::middleware(['auth', 'auth.rosalana'])->group(function () {
    Route::get('/me', function (Request $request) {
        return $request->user();
    });
});

require __DIR__.'/auth.php';
