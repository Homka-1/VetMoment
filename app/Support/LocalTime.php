<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Даты и время для интерфейса: в БД — UTC, показываем в config('app.display_timezone') (Asia/Almaty).
 */
final class LocalTime
{
    public static function of(CarbonInterface $moment): Carbon
    {
        return Carbon::instance($moment)->setTimezone(config('app.display_timezone'))->locale('ru');
    }

    /** «8 ноября»; год добавляется, если он не текущий: «8 ноября 2025». */
    public static function date(CarbonInterface $moment): string
    {
        $local = self::of($moment);
        $format = $local->year === self::of(now())->year ? 'j F' : 'j F Y';

        return $local->translatedFormat($format);
    }

    /** «10:00» */
    public static function time(CarbonInterface $moment): string
    {
        return self::of($moment)->format('H:i');
    }

    /** «8 ноября, 10:00» */
    public static function dateTime(CarbonInterface $moment): string
    {
        return self::date($moment).', '.self::time($moment);
    }

    /** «10:00–11:00» */
    public static function timeRange(CarbonInterface $start, CarbonInterface $end): string
    {
        return self::time($start).'–'.self::time($end);
    }

    /** «сб» */
    public static function weekdayShort(CarbonInterface $moment): string
    {
        return self::of($moment)->isoFormat('dd');
    }

    /** «суббота, 8 ноября» */
    public static function dayLong(CarbonInterface $moment): string
    {
        $local = self::of($moment);

        return $local->isoFormat('dddd').', '.self::date($moment);
    }

    /** Значение для атрибута datetime у <time> (ISO 8601 с поясом). */
    public static function iso(CarbonInterface $moment): string
    {
        return self::of($moment)->toIso8601String();
    }
}
