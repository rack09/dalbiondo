<?php

use App\Http\Controllers\Api\PizzaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpeningHourController;

Route::get('/pizzas', [PizzaController::class, 'index']);
Route::get('/opening-hours', [OpeningHourController::class, 'index']);