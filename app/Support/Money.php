<?php

namespace App\Support;

/**
 * Деньги в интерфейсе: суммы хранятся в тиынах (1 ₸ = 100 тиын), выводятся как «1 500 ₸».
 * Разделитель разрядов и пробел перед ₸ — неразрывные (U+00A0), чтобы сумма не переносилась.
 */
final class Money
{
    public const NBSP = "\u{00A0}";

    public const SIGN = '₸';

    /** «1 500 ₸»; тиыны показываются, только если они есть: «1 500,50 ₸». */
    public static function format(int $tiyn, bool $withSign = true): string
    {
        $negative = $tiyn < 0;
        $tiyn = abs($tiyn);
        $tenge = intdiv($tiyn, 100);
        $rest = $tiyn % 100;

        $number = number_format($tenge, 0, ',', self::NBSP);

        if ($rest !== 0) {
            $number .= ','.str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
        }

        return ($negative ? '−' : '').$number.($withSign ? self::NBSP.self::SIGN : '');
    }

    /** Целые тенге → тиыны (для форм, где вводят сумму в тенге). */
    public static function fromTenge(int|float|string $tenge): int
    {
        return (int) round(((float) str_replace([' ', self::NBSP, ','], ['', '', '.'], (string) $tenge)) * 100);
    }

    /** Короткая запись для крупных цифр: «2,1 млн ₸», «730 500 ₸» (до миллиона — полностью). */
    public static function compact(int $tiyn): string
    {
        $tenge = intdiv(abs($tiyn), 100);

        if ($tenge < 1_000_000) {
            return self::format($tiyn);
        }

        $millions = number_format($tenge / 1_000_000, 1, ',', '');
        $millions = str_ends_with($millions, ',0') ? substr($millions, 0, -2) : $millions;

        return ($tiyn < 0 ? '−' : '').$millions.self::NBSP.'млн'.self::NBSP.self::SIGN;
    }
}
