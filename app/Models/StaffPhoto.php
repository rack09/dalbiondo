<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['path', 'is_static', 'caption', 'alt', 'width', 'height', 'is_visible'])]
class StaffPhoto extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_static' => 'boolean',
            'is_visible' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }
}
