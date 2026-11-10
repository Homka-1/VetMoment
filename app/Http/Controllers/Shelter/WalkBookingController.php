<?php

namespace App\Http\Controllers\Shelter;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\WalkBookingDecisionRequest;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Services\WalkBookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Записи на выгул приюта (/shelter/bookings): подтвердить или отклонить, после прогулки — «Состоялся» / «Не пришёл».
 */
class WalkBookingController extends Controller
{
    public const TABS = [
        'pending' => 'Ждут подтверждения',
        'upcoming' => 'Предстоящие',
        'to_mark' => 'Отметить',
        'archive' => 'Архив',
    ];

    /** Тексты кнопок и тостов: кнопка и результат называются одинаково. */
    public const ACTIONS = [
        'confirmed' => ['button' => 'Подтвердить', 'done' => 'Запись подтверждена'],
        'rejected' => ['button' => 'Отклонить', 'done' => 'Запись отклонена'],
        'cancelled' => ['button' => 'Отменить запись', 'done' => 'Запись отменена'],
        'completed' => ['button' => 'Состоялся', 'done' => 'Отмечено: выгул состоялся'],
        'no_show' => ['button' => 'Не пришёл', 'done' => 'Отмечено: волонтёр не пришёл'],
    ];

    public function index(Request $request, WalkBookingService $service): View
    {
        $shelter = $request->user()->shelter();
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'pending';

        $counts = [];
        foreach (array_keys(self::TABS) as $key) {
            $counts[$key] = $this->query($shelter->id, $key)->count();
        }

        $bookings = $this->query($shelter->id, $tab)
            ->orderBy(WalkSlot::query()->select('starts_at')->whereColumn('walk_slots.id', 'walk_bookings.walk_slot_id'), $tab === 'archive' ? 'desc' : 'asc')
            ->orderBy('id')
            ->with(['slot', 'user', 'pet'])
            ->paginate(20)->withQueryString();

        $users = $bookings->getCollection()->pluck('user')->unique('id');

        return view('shelter.bookings.index', [
            'bookings' => $bookings,
            'tab' => $tab,
            'counts' => $counts,
            'next' => $bookings->getCollection()->mapWithKeys(fn (WalkBooking $b) => [$b->id => $service->nextStatuses($b)]),
            'noShows' => $users->mapWithKeys(fn ($u) => [$u->id => $service->hasManyNoShows($u)]),
        ]);
    }

    public function update(WalkBookingDecisionRequest $request, WalkBooking $booking, WalkBookingService $service): RedirectResponse
    {
        $status = $request->status();
        $service->process($booking, $status, $request->validated('shelter_comment'));

        return back()->with('status', self::ACTIONS[$status->value]['done']);
    }

    /**
     * @return Builder<WalkBooking>
     */
    private function query(int $shelterId, string $tab): Builder
    {
        $query = WalkBooking::query()->whereHas('slot', fn (Builder $q) => $q->where('shelter_id', $shelterId));
        $future = fn (Builder $q) => $q->where('starts_at', '>', now());
        $started = fn (Builder $q) => $q->where('starts_at', '<=', now());

        return match ($tab) {
            'pending' => $query->where('status', BookingStatus::Pending)->whereHas('slot', $future),
            'upcoming' => $query->where('status', BookingStatus::Confirmed)->whereHas('slot', $future),
            'to_mark' => $query->whereIn('status', BookingStatus::active())->whereHas('slot', $started),
            default => $query->whereNotIn('status', BookingStatus::active()),
        };
    }
}
