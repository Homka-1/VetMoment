<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Services\WalkBookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Мои выгулы» (/cabinet/walks): предстоящие и прошедшие записи, переход к записи и отмена.
 */
class WalkController extends Controller
{
    public const TABS = [
        'upcoming' => 'Предстоящие',
        'past' => 'Прошедшие',
    ];

    public function index(Request $request, WalkBookingService $service): View
    {
        $user = $request->user();
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'upcoming';

        $base = fn () => WalkBooking::query()->where('user_id', $user->getKey());
        $upcoming = fn (Builder $q) => $q->where('starts_at', '>', now());
        $past = fn (Builder $q) => $q->where('starts_at', '<=', now());

        $counts = [
            'upcoming' => $base()->whereHas('slot', $upcoming)->count(),
            'past' => $base()->whereHas('slot', $past)->count(),
        ];

        $bookings = $base()
            ->whereHas('slot', $tab === 'upcoming' ? $upcoming : $past)
            ->orderBy(WalkSlot::query()->select('starts_at')->whereColumn('walk_slots.id', 'walk_bookings.walk_slot_id'), $tab === 'upcoming' ? 'asc' : 'desc')
            ->with(['slot.shelter', 'pet.cover'])
            ->paginate(20)->withQueryString();

        return view('cabinet.walks', [
            'bookings' => $bookings,
            'tab' => $tab,
            'counts' => $counts,
            'activeCount' => $service->upcomingActive($user)->count(),
            'limit' => (int) config('walks.max_active_bookings', 3),
            'manyNoShows' => $service->hasManyNoShows($user),
        ]);
    }
}
