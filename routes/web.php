<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StaffPizzaController;

Route::get('/', function () {
    return view('app');
});

Route::prefix('api')->group(function () {
    Route::post('/session', [SessionController::class, 'store'])
        ->middleware('throttle:5,1,session-login');

    Route::middleware('auth:web')->group(function () {
        Route::get('/session', [SessionController::class, 'show']);
        Route::delete('/session', [SessionController::class, 'destroy']);
        Route::get('/staff/pizzas', [StaffPizzaController::class, 'index']);
        Route::post('/staff/pizzas', [StaffPizzaController::class, 'store']);
        Route::patch('/staff/pizzas/{pizza}', [StaffPizzaController::class, 'update']);
    });
});

Route::view('/area-riservata', 'app', ['page' => 'staff'])->name('login');
