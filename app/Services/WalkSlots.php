<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Enums\WalkSlotStatus;
use App\Models\Shelter;
use App\Models\WalkSlot;
use App\Models\WalkTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Слоты выгула глазами приюта (docs/product/user-flows.md, 4): разовый слот, шаблон недели,
 * изменение слота с записями и отмена слота целиком.
 *
 * Время из форм — местное (config('app.display_timezone')), в БД — UTC.
 */
class WalkSlots
{
    /**
     * @param  array{starts_at: Carbon, ends_at: Carbon, capacity: int, meeting_point: ?string, pets: list<int>}  $data
     */
    public function create(Shelter $shelter, array $data): WalkSlot
    {
        return DB::transaction(function () use ($shelter, $data): WalkSlot {
            if ($this->existsAt($shelter, $data['starts_at'])) {
                throw ValidationException::withMessages(['start_time' => 'На это время у приюта уже есть слот.']);
            }

            $slot = $shelter->walkSlots()->create([
                'starts_at' => $data['starts_at']->copy()->utc(),
                'ends_at' => $data['ends_at']->copy()->utc(),
                'capacity' => $data['capacity'],
                'meeting_point' => $data['meeting_point'],
                'status' => WalkSlotStatus::Open,
            ]);
            $slot->pets()->sync($data['pets']);

            return $slot;
        });
    }

    /**
     * Шаблон недели: сохраняет шаблон и создаёт слоты на период, пропуская время, где слот уже есть.
     *
     * @param  array{weekdays: list<int>, start_time: string, duration_minutes: int, capacity: int, meeting_point: ?string, valid_from: Carbon, valid_until: Carbon, pets: list<int>}  $data
     * @return array{template: WalkTemplate, created: int, skipped: int}
     */
    public function createFromTemplate(Shelter $shelter, array $data): array
    {
        return DB::transaction(function () use ($shelter, $data): array {
            $template = $shelter->walkTemplates()->create([
                'weekdays' => WalkTemplate::maskFromDays($data['weekdays']),
                'start_time' => $data['start_time'],
                'duration_minutes' => $data['duration_minutes'],
                'capacity' => $data['capacity'],
                'meeting_point' => $data['meeting_point'],
                'valid_from' => $data['valid_from']->toDateString(),
                'valid_until' => $data['valid_until']->toDateString(),
            ]);
            $template->pets()->sync($data['pets']);

            [$created, $skipped] = $this->generate($template, $data['pets']);

            return ['template' => $template, 'created' => $created, 'skipped' => $skipped];
        });
    }

    /**
     * Создаёт слоты шаблона на его период (только будущие).
     *
     * @param  list<int>  $pets
     * @return array{0: int, 1: int} создано, пропущено (уже был слот)
     */
    public function generate(WalkTemplate $template, array $pets): array
    {
        $tz = config('app.display_timezone');
        [$h, $m] = array_map('intval', explode(':', substr((string) $template->start_time, 0, 5)));
        $from = Carbon::parse($template->valid_from->toDateString(), $tz);
        $until = Carbon::parse($template->valid_until->toDateString(), $tz);
        $created = $skipped = 0;

        for ($day = $from->copy(); $day->lte($until); $day->addDay()) {
            if (! $template->hasWeekday($day->dayOfWeekIso)) {
                continue;
            }

            $startsAt = $day->copy()->setTime($h, $m);
            if ($startsAt->lessThanOrEqualTo(now())) {
                continue;
            }

            if ($this->existsAt($template->shelter, $startsAt)) {
                $skipped++;

                continue;
            }

            $slot = $template->shelter->walkSlots()->create([
                'template_id' => $template->getKey(),
                'starts_at' => $startsAt->copy()->utc(),
                'ends_at' => $startsAt->copy()->addMinutes($template->duration_minutes)->utc(),
                'capacity' => $template->capacity,
                'meeting_point' => $template->meeting_point,
                'status' => WalkSlotStatus::Open,
            ]);
            $slot->pets()->sync($pets);
            $created++;
        }

        return [$created, $skipped];
    }

    /**
     * Изменение слота. С активными записями нельзя менять время и ставить мест меньше, чем записано;
     * нельзя убрать собаку, которую уже выбрал волонтёр.
     *
     * @param  array{starts_at: Carbon, ends_at: Carbon, capacity: int, meeting_point: ?string, pets: list<int>}  $data
     */
    public function update(WalkSlot $slot, array $data): void
    {
        DB::transaction(function () use ($slot, $data): void {
            $slot = WalkSlot::query()->whereKey($slot->getKey())->lockForUpdate()->firstOrFail();
            $slot->recalculateBookedCount();
            $booked = $slot->booked_count;
            $errors = [];

            $timeChanged = ! $data['starts_at']->equalTo($slot->starts_at) || ! $data['ends_at']->equalTo($slot->ends_at);
            if ($timeChanged && $booked > 0) {
                $errors['start_time'] = 'На слот уже записались — время менять нельзя. Отмените слот и создайте новый.';
            } elseif ($timeChanged && $this->existsAt($slot->shelter, $data['starts_at'], except: $slot)) {
                $errors['start_time'] = 'На это время у приюта уже есть слот.';
            }

            if ($data['capacity'] < $booked) {
                $errors['capacity'] = "Записано уже {$booked} — мест не может быть меньше.";
            }

            $chosen = $slot->activeBookings()->whereNotNull('pet_id')->pluck('pet_id')->map(fn ($id): int => (int) $id)->all();
            if (array_diff($chosen, $data['pets']) !== [] && $data['pets'] !== []) {
                $errors['pets'] = 'Нельзя убрать собаку, которую уже выбрал волонтёр.';
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $slot->update([
                'starts_at' => $data['starts_at']->copy()->utc(),
                'ends_at' => $data['ends_at']->copy()->utc(),
                'capacity' => $data['capacity'],
                'meeting_point' => $data['meeting_point'],
            ]);
            $slot->pets()->sync($data['pets']);
        });
    }

    /** Отмена слота: все активные записи — «Отменена» приютом с причиной. */
    public function cancel(WalkSlot $slot, string $reason): int
    {
        return DB::transaction(function () use ($slot, $reason): int {
            $slot = WalkSlot::query()->whereKey($slot->getKey())->lockForUpdate()->firstOrFail();

            $cancelled = $slot->activeBookings()->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_by' => CancelledBy::Shelter,
                'cancelled_at' => now(),
                'shelter_comment' => $reason,
            ]);

            $slot->update(['status' => WalkSlotStatus::Cancelled]);
            $slot->recalculateBookedCount();

            return $cancelled;
        });
    }

    /** Удаление шаблона: будущие слоты без записей удаляются, слоты с записями остаются. */
    public function deleteTemplate(WalkTemplate $template): int
    {
        return DB::transaction(function () use ($template): int {
            $removed = $template->slots()
                ->where('starts_at', '>', now())
                ->whereDoesntHave('bookings')
                ->delete();

            $template->delete();

            return $removed;
        });
    }

    private function existsAt(Shelter $shelter, Carbon $startsAt, ?WalkSlot $except = null): bool
    {
        return $shelter->walkSlots()
            ->where('status', WalkSlotStatus::Open)
            ->where('starts_at', $startsAt->copy()->utc())
            ->when($except, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->exists();
    }
}
