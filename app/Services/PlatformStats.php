<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PetStatus;
use App\Models\Donation;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Цифры проекта: плашка на главной и статистика администратора.
 * Считаем агрегатами по БД; недели для графика группируем в PHP (без SQL-функций дат — код независим от СУБД).
 */
class PlatformStats
{
    /**
     * Итоги за всё время — «Что получилось вместе» на главной.
     *
     * @return array{adopted: int, raised: int, walks: int, shelters: int, cities: int}
     */
    public function overall(): array
    {
        $shelters = Shelter::query()->approved();

        return [
            'adopted' => Pet::query()->where('status', PetStatus::Adopted)->whereHas('shelter', fn (Builder $q) => $q->approved())->count(),
            'raised' => (int) Donation::query()->where('status', PaymentStatus::Succeeded)->sum('amount'),
            'walks' => WalkBooking::query()->where('status', BookingStatus::Completed)->count(),
            'shelters' => (clone $shelters)->count(),
            'cities' => (clone $shelters)->distinct()->count('city'),
        ];
    }

    /**
     * Месяц для статистики администратора (границы по Asia/Almaty, в запросах — UTC).
     *
     * @return array{adopted: int, raised: int, donations: int, walks: int, shelters: int, noShows: int, noShowRate: float, weeks: list<array{label: string, count: int}>}
     */
    public function month(CarbonImmutable $month): array
    {
        $tz = config('app.display_timezone', 'Asia/Almaty');
        $start = $month->setTimezone($tz)->startOfMonth();
        $end = $start->endOfMonth();

        $inSlot = fn (Builder $q) => $q->whereBetween('starts_at', [$start->utc(), $end->utc()]);

        $walks = WalkBooking::query()->where('status', BookingStatus::Completed)->whereHas('slot', $inSlot);
        $noShows = WalkBooking::query()->where('status', BookingStatus::NoShow)->whereHas('slot', $inSlot)->count();
        $walksCount = (clone $walks)->count();
        $held = $walksCount + $noShows;

        $donations = Donation::query()->where('status', PaymentStatus::Succeeded)->whereBetween('paid_at', [$start->utc(), $end->utc()]);

        return [
            'adopted' => Pet::query()->where('status', PetStatus::Adopted)->whereBetween('adopted_at', [$start->utc(), $end->utc()])->count(),
            'raised' => (int) (clone $donations)->sum('amount'),
            'donations' => (clone $donations)->count(),
            'walks' => $walksCount,
            'shelters' => (int) WalkSlot::query()->whereBetween('starts_at', [$start->utc(), $end->utc()])
                ->whereHas('bookings', fn (Builder $q) => $q->where('status', BookingStatus::Completed))
                ->distinct()->count('shelter_id'),
            'noShows' => $noShows,
            'noShowRate' => $held > 0 ? round($noShows / $held * 100, 1) : 0.0,
            'weeks' => $this->weeks($start, $end),
        ];
    }

    /**
     * Состоявшиеся прогулки по неделям месяца (недели с понедельника).
     *
     * @return list<array{label: string, count: int}>
     */
    private function weeks(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $weekStart = $start->startOfWeek();
        $starts = WalkBooking::query()
            ->where('walk_bookings.status', BookingStatus::Completed)
            ->join('walk_slots', 'walk_slots.id', '=', 'walk_bookings.walk_slot_id')
            ->whereBetween('walk_slots.starts_at', [$start->utc(), $end->utc()])
            ->pluck('walk_slots.starts_at');

        $tz = $start->getTimezone();
        $result = [];

        for ($week = $weekStart; $week->lessThanOrEqualTo($end); $week = $week->addWeek()) {
            $next = $week->addWeek();
            $count = $starts->filter(function ($moment) use ($week, $next, $tz): bool {
                $local = CarbonImmutable::parse($moment, 'UTC')->setTimezone($tz);

                return $local->greaterThanOrEqualTo($week) && $local->lessThan($next);
            })->count();

            $result[] = ['label' => $week->locale('ru')->translatedFormat('j M'), 'count' => $count];
        }

        return $result;
    }
}
