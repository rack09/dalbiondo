<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pizza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class StaffPizzaController extends Controller
{
    private const FIELDS = [
        'id',
        'name',
        'ingredients',
        'price_trancio',
        'price_teglia',
        'is_visible',
        'is_featured',
        'is_house_pizza',
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

        $saved = DB::transaction(function () use ($pizza, $data): array {
            $pizza->refreshForUpdate();
            $wasVisible = $pizza->is_visible;

            $pizza->fill($data);

            if (! $wasVisible || ! $pizza->is_visible) {
                $pizza->is_featured = false;
                $pizza->is_house_pizza = false;
            }

            $pizza->save();

            return $pizza->refresh()->only(self::FIELDS);
        }, 3);

        return response()->json($saved);
    }


    public function updateFeatured(Request $request, Pizza $pizza): JsonResponse
    {
        $data = $request->validate([
            'is_featured' => ['required', 'boolean'],
            'is_house_pizza' => ['sometimes', 'required', 'boolean'],
        ]);

        $ownerId = $request->user('web')->id;

        $saved = DB::transaction(function () use ($ownerId, $pizza, $data): array {
            User::query()->lockForUpdate()->findOrFail($ownerId);
            $pizza->refreshForUpdate();

            $featured = (bool) $data['is_featured'];
            $house = $featured && (bool) ($data['is_house_pizza'] ?? $pizza->is_house_pizza);

            if ($featured && ! $pizza->is_visible) {
                throw ValidationException::withMessages([
                    'is_featured' => ['Mostra la pizza prima di metterla in evidenza.'],
                ]);
            }

            if (
                $featured &&
                ! $pizza->is_featured &&
                Pizza::query()
                ->where('is_visible', true)
                ->where('is_featured', true)
                ->count() >= 6
            ) {
                throw ValidationException::withMessages([
                    'is_featured' => ['Puoi mettere in evidenza al massimo 6 pizze.'],
                ]);
            }

            if ($house) {
                Pizza::query()
                    ->where('is_house_pizza', true)
                    ->where('id', '!=', $pizza->id)
                    ->update(['is_house_pizza' => false]);
            }

            $pizza->is_featured = $featured;
            $pizza->is_house_pizza = $house;
            $pizza->save();

            return $pizza->only(['is_featured', 'is_house_pizza']);
        }, 3);

        return response()->json($saved);
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
