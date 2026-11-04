<?php

namespace Tests\Unit\Models;

use App\Models\WalkTemplate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WalkTemplateWeekdaysTest extends TestCase
{
    public function test_mask_from_days(): void
    {
        $this->assertSame(1, WalkTemplate::maskFromDays([1]));
        $this->assertSame(64, WalkTemplate::maskFromDays([7]));
        $this->assertSame(96, WalkTemplate::maskFromDays([6, 7]));
        $this->assertSame(127, WalkTemplate::maskFromDays(range(1, 7)));
        $this->assertSame(0, WalkTemplate::maskFromDays([]));
    }

    public function test_weekday_list_and_check(): void
    {
        $template = new WalkTemplate(['weekdays' => WalkTemplate::maskFromDays([2, 4])]);

        $this->assertSame([2, 4], $template->weekdayList());
        $this->assertTrue($template->hasWeekday(4));
        $this->assertFalse($template->hasWeekday(5));
    }

    public function test_invalid_day_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WalkTemplate::maskFromDays([0]);
    }
}
