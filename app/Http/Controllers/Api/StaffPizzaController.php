<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pizza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffPizzaController extends Controller
{
    private const FIELDS = [
        'id', 'name', 'ingredients', 'price_trancio', 'price_teglia', 'is_visible',
    ];

    public function index(): JsonResponse
    {
        $pizzas = Pizza::query()->orderBy('name')->get(self::FIELDS);

        return response()->json($pizzas);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePizza($request);
        $pizza = Pizza::create($data);

        return response()->json($pizza->refresh()->only(self::FIELDS), 201);
    }

    public function update(Request $request, Pizza $pizza): JsonResponse
    {
        $data = $this->validatePizza($request, partial: true);

        if ($data === []) {
            return response()->json([
                'message' => 'Invia almeno un campo da aggiornare.',
            ], 422);
        }

        $pizza->update($data);

        return response()->json($pizza->refresh()->only(array_keys($data)));
    }

    private function validatePizza(Request $request, bool $partial = false): array
    {
        $presence = $partial ? ['sometimes', 'required'] : ['required'];
        $priceRules = [...$presence, 'numeric', 'decimal:0,2', 'between:0,9999.99'];

        $data = $request->validate([
            'name' => [...$presence, 'string', 'max:255'],
            'ingredients' => [...$presence, 'string'],
            'price_trancio' => $priceRules,
            'price_teglia' => $priceRules,
            'is_visible' => ['sometimes', 'required', 'boolean'],
        ]);

        if (strlen($data['ingredients'] ?? '') > 65535) {
            throw ValidationException::withMessages([
                'ingredients' => ['Il testo degli ingredienti è troppo lungo.'],
            ]);
        }

        return $data;
    }
}