<?php

namespace App\Http\Controllers\Shelter;

use App\Enums\WalkSlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\WalkSlotCancelRequest;
use App\Http\Requests\Shelter\WalkSlotRequest;
use App\Models\WalkSlot;
use App\Services\WalkBookingService;
use App\Services\WalkSlots;
use App\Support\Plural;
use App\Support\WalkWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Слоты выгула приюта (макет 10-slots): календарь недели, разовый слот, страница слота с записями,
 * изменение и отмена слота.
 */
class WalkSlotController extends Controller
{
    public function index(Request $request): View
    {
        $shelter = $request->user()->shelter();
        $week = WalkWeek::from($request->query('week'));

        $slots = $shelter->walkSlots()
            ->where('starts_at', '>=', $week->start->copy()->utc())
            ->where('starts_at', '<', $week->end()->utc())
            ->withCount(['bookings as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->orderBy('starts_at')
            ->get();

        return view('shelter.slots.index', [
            'shelter' => $shelter,
            'week' => $week,
            'days' => $week->days($slots),
            'dogs' => $shelter->pets()->walkable()->orderBy('name')->get(['id', 'name']),
            'canCreate' => $request->user()->can('create', WalkSlot::class),
            'defaultDate' => $week->start->isPast() ? now(config('app.display_timezone'))->addDay()->startOfDay() : $week->start,
        ]);
    }

    public function store(WalkSlotRequest $request, WalkSlots $slots): RedirectResponse
    {
        $slot = $slots->create($request->shelter(), $request->slotData());

        return redirect()->route('shelter.slots.index', ['week' => WalkWeek::mondayOf($slot->starts_at)->format('Y-m-d')])
            ->with('status', 'Слот создан');
    }

    public function show(WalkSlot $slot, WalkBookingService $service): View
    {
        Gate::authorize('viewBookings', $slot);

        $slot->load(['shelter', 'pets', 'template']);
        $bookings = $slot->bookings()->with(['user', 'pet'])->orderByRaw("CASE WHEN status IN ('pending', 'confirmed') THEN 0 ELSE 1 END")->oldest()->get();

        return view('shelter.slots.show', [
            'slot' => $slot,
            'bookings' => $bookings,
            'next' => $bookings->mapWithKeys(fn ($b) => [$b->id => $service->nextStatuses($b, $slot)]),
            'noShows' => $bookings->pluck('user')->unique('id')->mapWithKeys(fn ($u) => [$u->id => $service->hasManyNoShows($u)]),
            'dogs' => $slot->shelter->pets()->walkable()->orderBy('name')->get(['id', 'name'])
                ->merge($slot->pets)->unique('id')->sortBy('name')->values(),
            'editable' => $slot->status === WalkSlotStatus::Open && $slot->starts_at->isFuture(),
        ]);
    }

    public function update(WalkSlotRequest $request, WalkSlot $slot, WalkSlots $slots): RedirectResponse
    {
        abort_unless($slot->status === WalkSlotStatus::Open && $slot->starts_at->isFuture(), 403);

        $slots->update($slot, $request->slotData());

        return redirect()->route('shelter.slots.show', $slot)->with('status', 'Слот сохранён');
    }

    public function cancel(WalkSlotCancelRequest $request, WalkSlot $slot, WalkSlots $slots): RedirectResponse
    {
        abort_unless($slot->status === WalkSlotStatus::Open && $slot->starts_at->isFuture(), 403);

        $count = $slots->cancel($slot, $request->validated('reason'));

        return redirect()->route('shelter.slots.show', $slot)->with('status', $count > 0
            ? 'Слот отменён, '.Plural::format($count, ['запись отменена', 'записи отменены', 'записей отменено'])
            : 'Слот отменён');
    }
}
