<?php

use App\Http\Controllers\Api\PizzaController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\SiteContactController;
use App\Http\Controllers\Api\StaffGalleryController;
use App\Http\Controllers\Api\StaffPhotoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpeningHourController;

Route::get('/pizzas', [PizzaController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::get('/contact', [SiteContactController::class, 'show']);
Route::get('/staff-photos', [StaffPhotoController::class, 'index']);
Route::get('/staff-gallery', [StaffGalleryController::class, 'show']);
Route::get('/opening-hours', [OpeningHourController::class, 'index']);
