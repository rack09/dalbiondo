<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SiteContactController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\StaffPizzaController;
use App\Http\Controllers\Api\StaffPhotoController;
use App\Http\Controllers\Api\StaffInvitationController;
use App\Http\Controllers\Api\StaffCollaboratorController;
use App\Http\Controllers\Api\StaffGalleryController;
use App\Http\Controllers\Api\StaffProfileController;
use App\Http\Middleware\EnsureStaffAccountActive;

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

    Route::middleware(['auth:web', EnsureStaffAccountActive::class, 'auth.session'])->group(function () {
        Route::get('/session', [SessionController::class, 'show']);
        Route::delete('/session', [SessionController::class, 'destroy']);
        Route::put('/staff/contact', [SiteContactController::class, 'update'])
            ->can('manage-site-contact');
        Route::patch('/staff/profile', [StaffProfileController::class, 'update'])
            ->can('edit-own-profile');
        Route::put('/staff/profile/password', [StaffProfileController::class, 'updatePassword'])
            ->can('edit-own-profile')
            ->middleware('throttle:5,1,staff-profile-password');
        Route::get('/staff/pizzas', [StaffPizzaController::class, 'index']);
        Route::post('/staff/pizzas', [StaffPizzaController::class, 'store']);
        Route::get('/staff/menu-items', [MenuItemController::class, 'staffIndex']);
        Route::post('/staff/menu-items', [MenuItemController::class, 'store']);
        Route::patch('/staff/menu-items/{menuItem}', [MenuItemController::class, 'update']);
        Route::get('/staff/photos', [StaffPhotoController::class, 'staffIndex']);
        Route::post('/staff/photos', [StaffPhotoController::class, 'store']);
        Route::patch('/staff/photos/{staffPhoto}', [StaffPhotoController::class, 'update']);
        Route::patch('/staff/photos/{staffPhoto}/featured', [StaffPhotoController::class, 'updateFeatured']);
        Route::delete('/staff/photos/{staffPhoto}', [StaffPhotoController::class, 'destroy'])->withTrashed();
        Route::put('/staff/gallery', [StaffGalleryController::class, 'update'])
            ->can('manage-staff-gallery');
        Route::post('/staff/invitations', [StaffInvitationController::class, 'store'])
            ->can('manage-collaborators');

        Route::prefix('staff/collaborators')->middleware('can:manage-collaborators')->group(function () {
            Route::get('/', [StaffCollaboratorController::class, 'index']);
            Route::patch('/{collaborator}', [StaffCollaboratorController::class, 'update'])->whereNumber('collaborator');
            Route::put('/{collaborator}/password', [StaffCollaboratorController::class, 'updatePassword'])->whereNumber('collaborator');
            Route::patch('/{collaborator}/status', [StaffCollaboratorController::class, 'updateStatus'])->whereNumber('collaborator');
        });

        Route::patch('/staff/pizzas/{pizza}', [StaffPizzaController::class, 'update']);
        Route::patch('/staff/pizzas/{pizza}/featured', [StaffPizzaController::class, 'updateFeatured'])
        ->can('manage-featured-pizzas');

        Route::put('/staff/opening-hours/{openingHour:weekday}', [OpeningHourController::class, 'update'])
        ->where('openingHour', '[1-7]');
    });
});

Route::view('/area-riservata', 'app', ['page' => 'staff'])->name('login');
Route::view('/attiva-invito', 'app', ['page' => 'invitation']);
