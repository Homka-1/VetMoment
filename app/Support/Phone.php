<?php

namespace App\Support;

/**
 * Казахстанские мобильные номера.
 *
 * Хранение — E.164 без пробелов: «+77012345678».
 * Отображение — «+7 701 234 56 78».
 * Ввод принимается в любом привычном виде: «8 701 234 56 78», «7 701…», «+7 (701) 234-56-78», «701 234 56 78».
 */
final class Phone
{
    /** Казахстанский мобильный: код страны 7, затем код оператора 7XX и 7 цифр абонента. */
    private const PATTERN = '/^\+77\d{9}$/';

    private function __construct() {}

    /**
     * Приводит ввод пользователя к E.164 («+77XXXXXXXXX»).
     * Возвращает null, если это не казахстанский мобильный номер.
     */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Допустимы только цифры, пробелы, скобки, дефисы, точки и ведущий «+».
        if (! preg_match('/^\+?[\d\s().\-]+$/', $value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        $digits = match (true) {
            strlen($digits) === 11 && $digits[0] === '8' => '7'.substr($digits, 1),
            strlen($digits) === 10 => '7'.$digits,
            default => $digits,
        };

        $e164 = '+'.$digits;

        return preg_match(self::PATTERN, $e164) ? $e164 : null;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }

    /**
     * Форматирует номер для показа: «+7 701 234 56 78».
     * Нераспознанное значение возвращается как есть.
     */
    public static function format(?string $value): string
    {
        $e164 = self::normalize($value);

        if ($e164 === null) {
            return (string) $value;
        }

        return sprintf(
            '+7 %s %s %s %s',
            substr($e164, 2, 3),
            substr($e164, 5, 3),
            substr($e164, 8, 2),
            substr($e164, 10, 2),
        );
    }
}
