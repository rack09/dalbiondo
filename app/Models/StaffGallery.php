<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffGallery extends Model
{
    public static function settings(): self
    {
        return static::query()->findOrFail(1);
    }

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'featured_photo_id' => 'integer',
        ];
    }
}
