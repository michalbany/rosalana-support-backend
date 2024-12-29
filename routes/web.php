<?php

use App\Http\Controllers\v1\AppController;
use App\Http\Controllers\v1\DocController;
use App\Http\Controllers\v1\IssueController;
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


Route::group(['prefix' => 'apps'], function () {
    Route::get('/', [AppController::class, 'index'])->name('apps.index');
    Route::get('/{id}', [AppController::class, 'show'])->name('apps.show');
    Route::post('/', [AppController::class, 'store'])->name('apps.store');
    Route::delete('/{id}', [AppController::class, 'destroy'])->name('apps.destroy');
    Route::post('/{id}/disable', [AppController::class, 'disable'])->name('apps.disable');
    Route::post('/{id}/enable', [AppController::class, 'enable'])->name('apps.enable');
    Route::patch('/{id}', [AppController::class, 'update'])->name('apps.update');
    Route::post('/{id}/refresh', [AppController::class, 'refresh'])->name('apps.refresh');
});

Route::group(['prefix' => 'docs'], function () {
    Route::get('/', [DocController::class, 'index'])->name('docs.index');
    Route::get('/{id}', [DocController::class, 'show'])->name('docs.show');
    Route::post('/', [DocController::class, 'store'])->name('docs.store');
    Route::patch('/{id}', [DocController::class, 'update'])->name('docs.update');
    Route::delete('/{id}', [DocController::class, 'destroy'])->name('docs.destroy');
});

Route::group(['prefix' => 'issues'], function () {
    Route::get('/', [IssueController::class, 'index'])->name('issues.index');
    Route::get('/{id}', [IssueController::class, 'show'])->name('issues.show');
    Route::post('/', [IssueController::class, 'store'])->name('issues.store');
    Route::patch('/{id}', [IssueController::class, 'update'])->name('issues.update');
    Route::delete('/{id}', [IssueController::class, 'destroy'])->name('issues.destroy');
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
