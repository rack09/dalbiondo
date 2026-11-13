<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['weekday', 'periods'])]
class OpeningHour extends Model
{
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'periods' => 'array',
        ];
    }
}