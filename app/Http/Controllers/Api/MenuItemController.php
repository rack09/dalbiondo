<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    private const FIELDS = ['id', 'category', 'name', 'description', 'price', 'is_visible'];

    public function index(): JsonResponse
    {
        $items = MenuItem::query()
            ->where('is_visible', true)
            ->orderBy('id')
            ->get(self::FIELDS);

        return response()->json($items);
    }

    public function staffIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(MenuItem::CATEGORIES)],
        ]);

        $items = MenuItem::query()
            ->where('category', $data['category'])
            ->orderBy('name')
            ->get(self::FIELDS);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $item = MenuItem::create($this->validateItem($request));

        return response()->json($item->refresh()->only(self::FIELDS), 201);
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $data = $this->validateItem($request, partial: true);

        if ($data === []) {
            return response()->json(['message' => 'Invia almeno un campo da aggiornare.'], 422);
        }

        $menuItem->update($data);

        return response()->json($menuItem->refresh()->only(self::FIELDS));
    }

    private function validateItem(Request $request, bool $partial = false): array
    {
        $presence = $partial ? ['sometimes', 'required'] : ['required'];
        $rules = [
            'name' => [...$presence, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,9999.99'],
            'is_visible' => ['sometimes', 'required', 'boolean'],
        ];

        if (! $partial) {
            $rules['category'] = ['required', Rule::in(MenuItem::CATEGORIES)];
        }

        return $request->validate($rules);
    }
}
