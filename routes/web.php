<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StaffPizzaController;
use App\Http\Controllers\Api\StaffInvitationController;

use App\Http\Controllers\Api\StaffRegistrationController;

use App\Http\Controllers\Api\OpeningHourController;

Route::get('/', function () {
    return view('app');
});

Route::prefix('api')->group(function () {
    Route::post('/session', [SessionController::class, 'store'])
        ->middleware('throttle:5,1,session-login');

    Route::post('/staff/registrations', [StaffRegistrationController::class, 'store'])
        ->middleware('throttle:5,1,staff-registration');

    Route::middleware('auth:web')->group(function () {
        Route::get('/session', [SessionController::class, 'show']);
        Route::delete('/session', [SessionController::class, 'destroy']);
        Route::get('/staff/pizzas', [StaffPizzaController::class, 'index']);
        Route::post('/staff/pizzas', [StaffPizzaController::class, 'store']);
        Route::post('/staff/invitations', [StaffInvitationController::class, 'store'])
        ->can('manage-collaborators');
        Route::patch('/staff/pizzas/{pizza}', [StaffPizzaController::class, 'update']);

        Route::put('/staff/opening-hours/{openingHour:weekday}', [OpeningHourController::class, 'update'])
        ->where('openingHour', '[1-7]');
    });
});

Route::view('/area-riservata', 'app', ['page' => 'staff'])->name('login');
Route::view('/attiva-invito', 'app', ['page' => 'invitation']);
