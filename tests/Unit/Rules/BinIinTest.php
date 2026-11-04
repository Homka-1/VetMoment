<?php

namespace Tests\Unit\Rules;

use App\Rules\BinIin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BinIinTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validNumbers(): array
    {
        return [
            'БИН «Дом лап» из демо-данных' => ['170540012341'],
            'БИН «Мурлыка»' => ['190240098768'],
            'БИН «Хвостики»' => ['210140054322'],
            // Контрольный разряд через второй набор весов (первый расчёт даёт 10)
            'ИИН со вторым набором весов' => ['000000000101'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'неверный контрольный разряд' => ['170540012342'],
            '11 цифр' => ['17054001234'],
            '13 цифр' => ['1705400123411'],
            'буквы' => ['17054001234a'],
            'пусто' => [''],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_accepts_numbers_with_correct_check_digit(string $value): void
    {
        $this->assertTrue(BinIin::isValid($value));
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_malformed_numbers(string $value): void
    {
        $this->assertFalse(BinIin::isValid($value));
    }
}
