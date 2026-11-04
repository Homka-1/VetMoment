<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Проверяет, что значение — казахстанский мобильный номер.
 * Нормализацию перед проверкой уникальности делает Form Request (prepareForValidation + Phone::normalize).
 */
class KazakhPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Phone::isValid($value)) {
            $fail('validation.kz_phone')->translate();
        }
    }
}
