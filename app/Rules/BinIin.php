<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * БИН юрлица или ИИН физлица (Казахстан): 12 цифр, последняя — контрольный разряд.
 *
 * Контрольный разряд: сумма цифр 1–11 с весами 1…11 по модулю 11; если получилось 10 —
 * повторный расчёт с весами 3…11, 1, 2; если снова 10 — номер недействителен.
 */
class BinIin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail('validation.bin_iin')->translate();
        }
    }

    public static function isValid(string $value): bool
    {
        if (! preg_match('/^\d{12}$/', $value)) {
            return false;
        }

        $digits = array_map('intval', str_split($value));
        $control = self::checksum($digits, range(1, 11));

        if ($control === 10) {
            $control = self::checksum($digits, [3, 4, 5, 6, 7, 8, 9, 10, 11, 1, 2]);
        }

        return $control !== 10 && $control === $digits[11];
    }

    /**
     * @param  list<int>  $digits
     * @param  list<int>  $weights
     */
    private static function checksum(array $digits, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $i => $weight) {
            $sum += $digits[$i] * $weight;
        }

        return $sum % 11;
    }
}
