<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPasswordBytes implements ValidationRule
{
    public function validate(string $attribute, #[\SensitiveParameter] mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) > 72 || str_contains($value, "\0")) {
            $fail('Usa al massimo 72 byte ed evita i caratteri non validi.');
        }
    }
}
