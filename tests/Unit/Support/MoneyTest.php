<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public static function amounts(): array
    {
        $nb = "\u{00A0}";

        return [
            'ноль' => [0, "0{$nb}₸"],
            'меньше тысячи' => [500_00, "500{$nb}₸"],
            'тысячи с неразрывным пробелом' => [1_500_00, "1{$nb}500{$nb}₸"],
            'миллионы' => [5_000_000_00, "5{$nb}000{$nb}000{$nb}₸"],
            'с тиынами' => [1_500_50, "1{$nb}500,50{$nb}₸"],
            'тиыны с ведущим нулём' => [10_05, "10,05{$nb}₸"],
            'отрицательная' => [-200_00, "−200{$nb}₸"],
        ];
    }

    #[DataProvider('amounts')]
    public function test_format(int $tiyn, string $expected): void
    {
        $this->assertSame($expected, Money::format($tiyn));
    }

    public function test_format_without_sign(): void
    {
        $this->assertSame("412\u{00A0}500", Money::format(412_500_00, withSign: false));
    }

    public function test_from_tenge(): void
    {
        $this->assertSame(3_500_00, Money::fromTenge('3 500'));
        $this->assertSame(3_500_00, Money::fromTenge("3\u{00A0}500"));
        $this->assertSame(1_000_50, Money::fromTenge('1000,5'));
        $this->assertSame(200_00, Money::fromTenge(200));
    }

    public function test_compact_keeps_small_sums_and_shortens_millions(): void
    {
        $nbsp = "\u{00A0}";

        $this->assertSame("730{$nbsp}500{$nbsp}₸", Money::compact(730_500_00));
        $this->assertSame("2,1{$nbsp}млн{$nbsp}₸", Money::compact(2_100_000_00));
        $this->assertSame("2{$nbsp}млн{$nbsp}₸", Money::compact(2_000_000_00));
        $this->assertSame("18,4{$nbsp}млн{$nbsp}₸", Money::compact(18_430_000_00));
    }
}
