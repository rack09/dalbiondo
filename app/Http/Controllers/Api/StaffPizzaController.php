<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pizza;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

class StaffPizzaController extends Controller
{
    public function index(): JsonResponse
    {
        $pizzas = Pizza::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'ingredients',
                'price_trancio',
                'price_teglia',
                'is_visible',
            ]);

        return response()->json($pizzas);
    }

    public function update(Request $request, Pizza $pizza): JsonResponse
    {
        $data = $request->validate([
        'is_visible' => ['required', 'boolean'],
        ]);

        $pizza->is_visible = $data['is_visible'];
        $pizza->save();

        return response()->json([
        'is_visible' => $pizza->is_visible,
        ]);
    }
}