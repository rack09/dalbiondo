<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category', 'name', 'description', 'price', 'is_visible'])]
class MenuItem extends Model
{
    public const CATEGORIES = ['rotonde', 'sfizi', 'bibite'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_visible' => 'boolean',
        ];
    }
}
