<?php

namespace App\Support;

use App\Models\WalkSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Неделя календаря выгулов (Пн–Вс по времени Казахстана): начало недели из параметра ?week=Y-m-d,
 * границы листания и раскладка слотов по дням. Общая для страницы записи и кабинета приюта.
 */
final class WalkWeek
{
    private function __construct(public readonly Carbon $start) {}

    /** Неделя, в которую попадает дата (или текущая), но не раньше $min и не позже $max. */
    public static function from(?string $value, ?Carbon $min = null, ?Carbon $max = null): self
    {
        $tz = config('app.display_timezone');
        $date = null;

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $date = rescue(fn () => Carbon::createFromFormat('Y-m-d', $value, $tz), null, false);
        }

        $start = ($date ?? Carbon::now($tz))->copy()->locale('ru')->startOfWeek(Carbon::MONDAY)->startOfDay();

        if ($min && $start->lt($min)) {
            $start = $min->copy();
        }
        if ($max && $start->gt($max)) {
            $start = $max->copy();
        }

        return new self($start);
    }

    /** Понедельник недели, в которую попадает момент. */
    public static function mondayOf(Carbon $moment): Carbon
    {
        return LocalTime::of($moment)->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public function end(): Carbon
    {
        return $this->start->copy()->addWeek();
    }

    public function key(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function previousKey(): string
    {
        return $this->start->copy()->subWeek()->format('Y-m-d');
    }

    public function nextKey(): string
    {
        return $this->start->copy()->addWeek()->format('Y-m-d');
    }

    /** «3–9 ноября», «29 сентября – 5 октября» */
    public function label(): string
    {
        $last = $this->start->copy()->addDays(6);

        if ($this->start->month === $last->month) {
            return $this->start->day.'–'.LocalTime::date($last);
        }

        return LocalTime::date($this->start).' – '.LocalTime::date($last);
    }

    /**
     * Семь дней со слотами этого дня.
     *
     * @param  Collection<int, WalkSlot>  $slots
     * @return list<array{date: Carbon, key: string, slots: Collection<int, WalkSlot>, available: bool, today: bool, past: bool}>
     */
    public function days(Collection $slots): array
    {
        $today = Carbon::now(config('app.display_timezone'))->startOfDay();
        $byDay = $slots->groupBy(fn (WalkSlot $slot): string => LocalTime::of($slot->starts_at)->format('Y-m-d'));
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $this->start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $daySlots = ($byDay[$key] ?? collect())->sortBy('starts_at')->values();

            $days[] = [
                'date' => $date,
                'key' => $key,
                'slots' => $daySlots,
                'available' => $daySlots->contains(fn (WalkSlot $slot): bool => $slot->state()->isBookable()),
                'today' => $date->equalTo($today),
                'past' => $date->lt($today),
            ];
        }

        return $days;
    }
}
