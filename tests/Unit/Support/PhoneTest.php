<?php

namespace Tests\Unit\Support;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public static function validInputs(): array
    {
        return [
            'E.164' => ['+77012345678'],
            'с восьмёркой' => ['8 701 234 56 78'],
            'с семёркой без плюса' => ['7 701 234 56 78'],
            'со скобками и дефисами' => ['+7 (701) 234-56-78'],
            'без кода страны' => ['701 234 56 78'],
            'слитно с восьмёркой' => ['87012345678'],
            'с точками' => ['8.701.234.56.78'],
            'с пробелами по краям' => ['  +7 701 234 56 78  '],
        ];
    }

    public static function invalidInputs(): array
    {
        return [
            'пусто' => [''],
            'null' => [null],
            'буквы' => ['телефон'],
            'короткий' => ['+7 701 234'],
            'длинный' => ['+7 701 234 56 789'],
            'российский городской' => ['+7 495 123 45 67'],
            'другая страна' => ['+998 90 123 45 67'],
            'буквы внутри' => ['+7 701 abc 56 78'],
            'плюс в середине' => ['7+7012345678'],
        ];
    }

    #[DataProvider('validInputs')]
    public function test_normalizes_valid_numbers(string $input): void
    {
        $this->assertSame('+77012345678', Phone::normalize($input));
        $this->assertTrue(Phone::isValid($input));
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_numbers(?string $input): void
    {
        $this->assertNull(Phone::normalize($input));
        $this->assertFalse(Phone::isValid($input));
    }

    public function test_formats_for_display(): void
    {
        $this->assertSame('+7 701 234 56 78', Phone::format('+77012345678'));
        $this->assertSame('+7 777 000 11 22', Phone::format('8 (777) 000-11-22'));
    }

    public function test_format_returns_unrecognized_value_as_is(): void
    {
        $this->assertSame('12345', Phone::format('12345'));
        $this->assertSame('', Phone::format(null));
    }
}
