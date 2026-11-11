<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
//use Illuminate\Http\Request;

use App\Models\Pizza;
use Illuminate\Http\JsonResponse;

class PizzaController extends Controller
{
    public function index(): JsonResponse
    {
        $pizzas = Pizza::query()
            ->Where('is_visible',true)
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'ingredients',
                'price_trancio',
                'price_teglia',
            ]);

        return response()->json($pizzas);
    }
}
