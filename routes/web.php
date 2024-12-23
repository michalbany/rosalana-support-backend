<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Route::post('/test', function () {
//     return ['test' => 'ok'];
// });

Route::middleware(['auth', 'auth.rosalana'])->get('/test', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__.'/auth.php';
