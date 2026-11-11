<?php

use App\Http\Controllers\Api\PizzaController;
use Illuminate\Support\Facades\Route;

Route::get('/pizzas', [PizzaController::class, 'index']);