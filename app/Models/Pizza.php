<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
#[Fillable(['name', 'ingredients', 'price_trancio', 'price_teglia', 'is_visible'])]
class Pizza extends Model
{
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }
}
