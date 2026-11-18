<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffGallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffGalleryController extends Controller
{
    public function show(): JsonResponse
    {
        return $this->visibilityResponse(StaffGallery::settings());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        $gallery = StaffGallery::settings();
        $gallery->is_visible = $data['is_visible'];
        $gallery->save();

        return $this->visibilityResponse($gallery);
    }

    private function visibilityResponse(StaffGallery $gallery): JsonResponse
    {
        return response()->json(['is_visible' => $gallery->is_visible])
            ->header('Cache-Control', 'no-store');
    }
}
