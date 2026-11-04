<?php

namespace Tests\Unit\Support;

use App\Support\LocalTime;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocalTimeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_converts_utc_to_almaty(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $utc = Carbon::parse('2026-11-08 05:00:00', 'UTC');

        $this->assertSame('10:00', LocalTime::time($utc));
        $this->assertSame('8 ноября', LocalTime::date($utc));
        $this->assertSame('8 ноября, 10:00', LocalTime::dateTime($utc));
        $this->assertSame('10:00–11:00', LocalTime::timeRange($utc, $utc->copy()->addHour()));
        $this->assertSame('вс', LocalTime::weekdayShort($utc));
        $this->assertSame('воскресенье, 8 ноября', LocalTime::dayLong($utc));
    }

    public function test_date_after_midnight_in_almaty_is_next_day(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');

        $this->assertSame('9 ноября', LocalTime::date(Carbon::parse('2026-11-08 20:30:00', 'UTC')));
    }

    public function test_other_year_is_shown(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');

        $this->assertSame('8 ноября 2025', LocalTime::date(Carbon::parse('2025-11-08 05:00:00', 'UTC')));
    }
}
