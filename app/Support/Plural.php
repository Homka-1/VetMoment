<?php

namespace App\Support;

/**
 * Русские склонения после числительных: 1 год, 2 года, 5 лет.
 */
final class Plural
{
    /**
     * @param  array{0: string, 1: string, 2: string}  $forms  [один, два–четыре, пять]
     */
    public static function choose(int $n, array $forms): string
    {
        $n = abs($n);
        $mod100 = $n % 100;
        $mod10 = $n % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $forms[2];
        }

        return match (true) {
            $mod10 === 1 => $forms[0],
            $mod10 >= 2 && $mod10 <= 4 => $forms[1],
            default => $forms[2],
        };
    }

    /**
     * Число с подписью: «2 года», «21 человек».
     *
     * @param  array{0: string, 1: string, 2: string}  $forms
     */
    public static function format(int $n, array $forms): string
    {
        return number_format($n, 0, ',', Money::NBSP).Money::NBSP.self::choose($n, $forms);
    }

    /** Возраст по числу месяцев: «3 месяца», «1 год», «2 года»; меньше месяца — «меньше месяца». */
    public static function age(int $months): string
    {
        if ($months < 1) {
            return 'меньше месяца';
        }

        if ($months < 12) {
            return self::format($months, ['месяц', 'месяца', 'месяцев']);
        }

        return self::format(intdiv($months, 12), ['год', 'года', 'лет']);
    }
}
