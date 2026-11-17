<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Enums\WalkSlotState;
use App\Enums\WalkSlotStatus;
use App\Models\Pet;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Notifications\WalkBookingCancelled;
use App\Notifications\WalkBookingCreated;
use App\Notifications\WalkBookingDecided;
use App\Notifications\WalkReminder;
use App\Support\Plural;
use App\Support\Recipients;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Запись на выгул (docs/product/data-requirements.md, 7 — правила W1–W10; user-flows.md, 1).
 * Политики решают «кто» (WalkSlotPolicy::book, WalkBookingPolicy), здесь — «можно ли сейчас».
 *
 * Гонки (W9): запись и отмена идут в транзакции под lockForUpdate() на слот и на пользователя —
 * два человека не займут последнее место, один человек не обойдёт лимит двумя вкладками.
 * Счётчик booked_count пересчитывается агрегатом под той же блокировкой.
 *
 * Уведомления (этап 12) отправляются после коммита (AppNotification::afterCommit).
 */
class WalkBookingService
{
    /** Переходы при обработке записи приютом. */
    public const TRANSITIONS = [
        'pending' => [BookingStatus::Confirmed, BookingStatus::Rejected, BookingStatus::Completed, BookingStatus::NoShow],
        'confirmed' => [BookingStatus::Completed, BookingStatus::NoShow, BookingStatus::Cancelled],
    ];

    /** Отметки «состоялся» / «не пришёл» — только когда прогулка началась. */
    private const AFTER_START = [BookingStatus::Completed, BookingStatus::NoShow];

    public const UNCONFIRMED_COMMENT = 'Приют не успел подтвердить запись до начала прогулки.';

    /** Подтвердить, отклонить и отменить можно только до начала. */
    private const BEFORE_START = [BookingStatus::Confirmed, BookingStatus::Rejected, BookingStatus::Cancelled];

    /**
     * Создаёт запись `pending`.
     *
     * @throws ValidationException ошибка с ключом `slot_id` (время) или `pet_id` (питомец)
     */
    public function book(User $user, WalkSlot $slot, ?Pet $pet, ?string $comment = null): WalkBooking
    {
        return DB::transaction(function () use ($user, $slot, $pet, $comment): WalkBooking {
            /** @var WalkSlot $slot */
            $slot = WalkSlot::query()->whereKey($slot->getKey())->lockForUpdate()->firstOrFail();
            User::query()->whereKey($user->getKey())->lockForUpdate()->first();
            $slot->load('pets', 'shelter');
            $slot->recalculateBookedCount(); // места считаем по записям под блокировкой, а не по сохранённому счётчику

            $this->ensureSlotOpen($slot);
            $this->ensurePetAvailable($slot, $pet);
            $this->ensureUserFree($user, $slot);

            $booking = $slot->bookings()->create([
                'user_id' => $user->getKey(),
                'pet_id' => $pet?->getKey(),
                'status' => BookingStatus::Pending,
                'comment' => filled($comment) ? $comment : null,
            ]);

            $slot->recalculateBookedCount();

            Notification::send(Recipients::shelterStaff($slot->shelter), new WalkBookingCreated($booking->setRelation('slot', $slot)));

            return $booking;
        });
    }

    /** Отмена волонтёром — не позже чем за config('walks.cancel_before_hours') часов (W7). */
    public function cancelByUser(WalkBooking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $slot = WalkSlot::query()->whereKey($booking->walk_slot_id)->lockForUpdate()->firstOrFail();
            $booking->refresh();

            if (! $booking->status->isActive()) {
                throw ValidationException::withMessages(['booking' => 'Эта запись уже не активна.']);
            }

            if (! $this->userCanCancelInTime($booking, $slot)) {
                throw ValidationException::withMessages([
                    'booking' => 'Отменить запись на сайте можно не позже чем за '.$this->hours('walks.cancel_before_hours').'. Позвоните в приют.',
                ]);
            }

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_by' => CancelledBy::User,
                'cancelled_at' => now(),
            ]);

            $slot->recalculateBookedCount();

            Notification::send(Recipients::shelterStaff($slot->shelter_id), new WalkBookingCancelled($booking));
        });
    }

    /** Успевает ли волонтёр отменить запись сам (W7). */
    public function userCanCancelInTime(WalkBooking $booking, ?WalkSlot $slot = null): bool
    {
        $slot ??= $booking->slot;

        return $slot->starts_at->greaterThanOrEqualTo(now()->addHours((int) config('walks.cancel_before_hours', 3)));
    }

    /**
     * Решение приюта: подтвердить, отклонить (с причиной), отменить подтверждённую, «состоялся», «не пришёл».
     */
    public function process(WalkBooking $booking, BookingStatus $status, ?string $comment = null): void
    {
        DB::transaction(function () use ($booking, $status, $comment): void {
            $slot = WalkSlot::query()->whereKey($booking->walk_slot_id)->lockForUpdate()->firstOrFail();
            $booking->refresh();

            if (! in_array($status, $this->nextStatuses($booking, $slot), true)) {
                throw ValidationException::withMessages(['status' => 'Запись уже в другом статусе — обновите страницу.']);
            }

            $data = ['status' => $status];

            if ($status === BookingStatus::Confirmed) {
                $data['confirmed_at'] = now();
            }

            if (in_array($status, [BookingStatus::Rejected, BookingStatus::Cancelled], true)) {
                $data['shelter_comment'] = $comment;
            }

            if ($status === BookingStatus::Cancelled) {
                $data['cancelled_by'] = CancelledBy::Shelter;
                $data['cancelled_at'] = now();
            }

            $booking->update($data);
            $slot->recalculateBookedCount();

            if (in_array($status, self::BEFORE_START, true)) {
                $booking->user->notify(new WalkBookingDecided($booking));
            }
        });
    }

    /**
     * Что приют может сделать с записью сейчас.
     *
     * @return list<BookingStatus>
     */
    public function nextStatuses(WalkBooking $booking, ?WalkSlot $slot = null): array
    {
        $slot ??= $booking->slot;
        $started = $slot->starts_at->lessThanOrEqualTo(now());

        return array_values(array_filter(
            self::TRANSITIONS[$booking->status->value] ?? [],
            fn (BookingStatus $next): bool => $started
                ? in_array($next, self::AFTER_START, true)
                : in_array($next, self::BEFORE_START, true),
        ));
    }

    /**
     * Активные будущие записи пользователя (для лимита W6 и «Мои выгулы»).
     *
     * @return Builder<WalkBooking>
     */
    public function upcomingActive(User $user): Builder
    {
        return WalkBooking::query()
            ->where('user_id', $user->getKey())
            ->active()
            ->whereHas('slot', fn (Builder $q) => $q->where('starts_at', '>', now()));
    }

    /** Неявки за последние config('walks.no_show_window_days') дней (W10). */
    public function recentNoShows(User $user): int
    {
        return WalkBooking::query()
            ->where('user_id', $user->getKey())
            ->where('status', BookingStatus::NoShow)
            ->whereHas('slot', fn (Builder $q) => $q->where('starts_at', '>=', now()->subDays((int) config('walks.no_show_window_days', 90))))
            ->count();
    }

    /** Нужно ли предупредить волонтёра и приют о частых неявках (W10). */
    public function hasManyNoShows(User $user): bool
    {
        return $this->recentNoShows($user) >= (int) config('walks.no_show_limit', 3);
    }

    /**
     * Питомцы слота, которых уже выбрали другие (активные записи), — W4.
     *
     * @return list<int>
     */
    public function takenPetIds(WalkSlot $slot): array
    {
        return $slot->activeBookings()->whereNotNull('pet_id')->pluck('pet_id')->map(fn ($id): int => (int) $id)->all();
    }

    /** Активная запись пользователя в этом слоте, если есть. */
    public function activeBookingIn(User $user, WalkSlot $slot): ?WalkBooking
    {
        return $slot->activeBookings()->where('user_id', $user->getKey())->first();
    }

    /** W1–W3: слот открыт, не начался, запись не закрыта, в горизонте, есть места. */
    private function ensureSlotOpen(WalkSlot $slot): void
    {
        $message = match ($slot->state()) {
            WalkSlotState::Cancelled => 'Приют отменил эту прогулку. Выберите другое время.',
            WalkSlotState::Past => 'Эта прогулка уже прошла. Выберите другое время.',
            WalkSlotState::Closed => 'Запись на это время закрыта: записаться можно не позже чем за '.$this->hours('walks.book_before_hours').' до начала. Выберите другое время.',
            WalkSlotState::Full => 'Мест больше нет — пока вы выбирали, их заняли. Выберите другое время.',
            default => null,
        };

        if ($message === null && $slot->starts_at->greaterThan(WalkSlot::horizonEndsAt())) {
            $message = 'Запись на это время ещё не открыта: записаться можно на '.config('walks.horizon_days', 14).' дней вперёд.';
        }

        if ($message === null && ! $slot->shelter->isApproved()) {
            $message = 'Приют сейчас не принимает записи.';
        }

        if ($message !== null) {
            throw ValidationException::withMessages(['slot_id' => $message]);
        }
    }

    /** W4, W8: питомец из этого слота, готов к прогулке и ещё не выбран другим волонтёром. */
    private function ensurePetAvailable(WalkSlot $slot, ?Pet $pet): void
    {
        if ($pet === null) {
            return;
        }

        if (! $slot->offersPet($pet)) {
            throw ValidationException::withMessages(['pet_id' => "{$pet->name} не гуляет в это время. Выберите другую собаку."]);
        }

        if (! $pet->isWalkable() || $pet->is_hidden) {
            throw ValidationException::withMessages(['pet_id' => "С {$pet->name} сейчас нельзя погулять. Выберите другую собаку."]);
        }

        if (in_array($pet->getKey(), $this->takenPetIds($slot), true)) {
            throw ValidationException::withMessages([
                'pet_id' => "{$pet->name} уже гуляет в это время с другим волонтёром. Выберите другую собаку или «Любая собака».",
            ]);
        }
    }

    /** W5, W6: нет записи в этом слоте, нет пересечений, не превышен лимит активных записей. */
    private function ensureUserFree(User $user, WalkSlot $slot): void
    {
        $active = $this->upcomingActive($user)->with('slot')->get();

        if ($active->contains(fn (WalkBooking $b): bool => $b->walk_slot_id === $slot->getKey())) {
            throw ValidationException::withMessages(['slot_id' => 'Вы уже записаны на это время.']);
        }

        if ($active->contains(fn (WalkBooking $b): bool => $b->slot->overlaps($slot))) {
            throw ValidationException::withMessages([
                'slot_id' => 'В это время у вас уже есть другая прогулка. Выберите время, которое с ней не пересекается.',
            ]);
        }

        $limit = (int) config('walks.max_active_bookings', 3);
        if ($active->count() >= $limit) {
            throw ValidationException::withMessages([
                'slot_id' => "У вас уже {$limit} предстоящие записи — это максимум. Сходите на прогулку или отмените одну из записей.",
            ]);
        }
    }

    /**
     * Напоминания о подтверждённых прогулках, до которых осталось не больше config('walks.reminder_hours') часов.
     * Отметка reminder_sent_at ставится условным UPDATE: два параллельных запуска не пришлют письмо дважды.
     */
    public function sendReminders(): int
    {
        $sent = 0;

        WalkBooking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->whereHas('slot', fn (Builder $q) => $q
                ->where('status', WalkSlotStatus::Open)
                ->where('starts_at', '>', now())
                ->where('starts_at', '<=', now()->addHours((int) config('walks.reminder_hours', 24))))
            ->with('slot.shelter', 'pet', 'user')
            ->chunkById(100, function ($bookings) use (&$sent): void {
                foreach ($bookings as $booking) {
                    $claimed = WalkBooking::query()->whereKey($booking->getKey())->whereNull('reminder_sent_at')
                        ->update(['reminder_sent_at' => now()]);

                    if ($claimed === 1 && ! $booking->user->is_blocked) {
                        $booking->user->notify(new WalkReminder($booking));
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    /**
     * Записи, которые приют так и не подтвердил до начала прогулки, → «Отменена» системой; волонтёру — уведомление.
     */
    public function expireUnconfirmed(): int
    {
        $bookings = WalkBooking::query()
            ->where('status', BookingStatus::Pending)
            ->whereHas('slot', fn (Builder $q) => $q->where('starts_at', '<=', now()))
            ->with('slot.shelter', 'pet', 'user')
            ->get();

        foreach ($bookings->groupBy('walk_slot_id') as $slotBookings) {
            DB::transaction(function () use ($slotBookings): void {
                $slot = WalkSlot::query()->whereKey($slotBookings->first()->walk_slot_id)->lockForUpdate()->firstOrFail();

                foreach ($slotBookings as $booking) {
                    $updated = WalkBooking::query()->whereKey($booking->getKey())->where('status', BookingStatus::Pending)->update([
                        'status' => BookingStatus::Cancelled,
                        'cancelled_by' => CancelledBy::System,
                        'cancelled_at' => now(),
                        'shelter_comment' => self::UNCONFIRMED_COMMENT,
                        'updated_at' => now(),
                    ]);

                    if ($updated === 1) {
                        $booking->user->notify(new WalkBookingDecided($booking->refresh()->setRelation('slot', $slotBookings->first()->slot)));
                    }
                }

                $slot->recalculateBookedCount();
            });
        }

        return $bookings->count();
    }

    /** «3 часа» — для сообщений о сроках записи и отмены. */
    public function hours(string $key): string
    {
        $hours = (int) config($key);

        return Plural::format($hours, ['час', 'часа', 'часов']);
    }
}
