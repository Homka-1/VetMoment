<?php

namespace Tests\Unit\Support;

use App\Support\Plural;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PluralTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            [1, 'год'], [2, 'года'], [4, 'года'], [5, 'лет'], [11, 'лет'], [12, 'лет'], [14, 'лет'],
            [21, 'год'], [22, 'года'], [25, 'лет'], [101, 'год'], [111, 'лет'], [0, 'лет'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_choose(int $n, string $expected): void
    {
        $this->assertSame($expected, Plural::choose($n, ['год', 'года', 'лет']));
    }

    public function test_format_uses_non_breaking_space(): void
    {
        $this->assertSame("84\u{00A0}человека", Plural::format(84, ['человек', 'человека', 'человек']));
        $this->assertSame("1\u{00A0}250\u{00A0}человек", Plural::format(1250, ['человек', 'человека', 'человек']));
    }

    public function test_age(): void
    {
        $this->assertSame('меньше месяца', Plural::age(0));
        $this->assertSame("3\u{00A0}месяца", Plural::age(3));
        $this->assertSame("11\u{00A0}месяцев", Plural::age(11));
        $this->assertSame("1\u{00A0}год", Plural::age(12));
        $this->assertSame("2\u{00A0}года", Plural::age(29));
        $this->assertSame("6\u{00A0}лет", Plural::age(74));
    }
}
