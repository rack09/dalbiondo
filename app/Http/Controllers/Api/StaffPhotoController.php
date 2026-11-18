<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffGallery;
use App\Models\StaffPhoto;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class StaffPhotoController extends Controller
{
    public function index(): JsonResponse
    {
        $gallery = StaffGallery::settings();
        if (! $gallery->is_visible) {
            return response()->json([])->header('Cache-Control', 'no-store');
        }

        $featuredId = $gallery->featured_photo_id;
        $photos = StaffPhoto::query()
            ->where('is_visible', true)
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$featuredId])
            ->orderBy('id')
            ->get()
            ->map(fn (StaffPhoto $photo) => $this->photoData($photo, $featuredId));

        return response()->json($photos)->header('Cache-Control', 'no-store');
    }

    public function staffIndex(): JsonResponse
    {
        $featuredId = StaffGallery::settings()->featured_photo_id;
        $photos = StaffPhoto::query()
            ->orderBy('id')
            ->get()
            ->map(fn (StaffPhoto $photo) => $this->photoData($photo, $featuredId));

        return response()->json($photos)->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => [
                'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                'dimensions:max_width=6000,max_height=6000',
            ],
            'caption' => ['required', 'string', 'max:160'],
            'alt' => ['nullable', 'string', 'max:200'],
            'is_visible' => ['sometimes', 'boolean'],
        ]);

        $file = $request->file('photo');
        $dimensions = getimagesize($file->getPathname());
        if ($dimensions === false) {
            throw ValidationException::withMessages([
                'photo' => 'Seleziona una foto valida.',
            ]);
        }

        $path = $file->store('staff', 'public');
        if ($path === false) {
            return response()->json(['message' => 'Impossibile salvare il file della foto.'], 500);
        }

        try {
            $photo = StaffPhoto::create([
                'path' => $path,
                'is_static' => false,
                'caption' => $data['caption'],
                'alt' => $data['alt'] ?? $data['caption'],
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'is_visible' => $data['is_visible'] ?? false,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return response()->json($this->photoData($photo), 201);
    }

    public function update(Request $request, StaffPhoto $staffPhoto): JsonResponse
    {
        $data = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        return $this->updatePhoto($staffPhoto, function (StaffGallery $gallery, StaffPhoto $photo) use ($data): array {
            if (! $data['is_visible']) {
                $this->clearFeaturedPhoto($gallery, $photo);
            }

            $photo->update(['is_visible' => $data['is_visible']]);

            return $this->photoData($photo, $gallery->featured_photo_id);
        });
    }

    public function updateFeatured(Request $request, StaffPhoto $staffPhoto): JsonResponse
    {
        $data = $request->validate([
            'is_featured' => ['required', 'boolean'],
        ]);

        $featured = (bool) $data['is_featured'];

        return $this->updatePhoto($staffPhoto, function (StaffGallery $gallery, StaffPhoto $photo) use ($featured): array {
            if ($featured && ! $photo->is_visible) {
                throw ValidationException::withMessages([
                    'is_featured' => ['Mostra la foto prima di metterla in evidenza.'],
                ]);
            }

            if ($featured || $gallery->featured_photo_id === $photo->id) {
                $gallery->featured_photo_id = $featured ? $photo->id : null;
                $gallery->save();
            }

            return ['featured_photo_id' => $gallery->featured_photo_id];
        });
    }

    public function destroy(StaffPhoto $staffPhoto): JsonResponse
    {
        return $this->updatePhoto($staffPhoto, function (StaffGallery $gallery, StaffPhoto $photo): array {
            $this->clearFeaturedPhoto($gallery, $photo);

            if (! $photo->trashed()) {
                if (! $photo->update(['is_visible' => false]) || ! $photo->delete()) {
                    throw new RuntimeException('Impossibile eliminare la foto.');
                }
            }

            return ['id' => $photo->id];
        }, includeDeleted: true);
    }

    private function clearFeaturedPhoto(StaffGallery $gallery, StaffPhoto $photo): void
    {
        if ($gallery->featured_photo_id === $photo->id) {
            $gallery->featured_photo_id = null;
            $gallery->save();
        }
    }

    private function updatePhoto(StaffPhoto $photo, Closure $update, bool $includeDeleted = false): JsonResponse
    {
        try {
            $data = Cache::lock('staff-photos:edit', 15)->block(5, function () use ($photo, $update, $includeDeleted): array {
                return DB::transaction(
                    fn () => $update(
                        StaffGallery::settings(),
                        StaffPhoto::query()->withTrashed($includeDeleted)->findOrFail($photo->getKey()),
                    ),
                    3,
                );
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Il catalogo delle foto è in aggiornamento. Riprova.'], 409);
        }

        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    private function photoData(StaffPhoto $photo, ?int $featuredId = null): array
    {
        $publicPath = $photo->is_static ? $photo->path : 'storage/'.$photo->path;

        return [
            'id' => $photo->id,
            'url' => asset($publicPath),
            'caption' => $photo->caption,
            'alt' => $photo->alt,
            'width' => $photo->width,
            'height' => $photo->height,
            'is_visible' => $photo->is_visible,
            'is_featured' => $photo->is_visible && $photo->id === $featuredId,
        ];
    }
}
