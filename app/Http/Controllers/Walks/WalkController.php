<?php

namespace App\Http\Controllers\Walks;

use App\Enums\WalkSlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Walks\WalkBookingRequest;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkSlot;
use App\Services\WalkBookingService;
use App\Support\WalkWeek;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Запись на выгул (макет 06, docs/product/user-flows.md, 1) — три шага:
 * приют и время (/walks) → подтверждение (/walks/slots/{id}) → запись (/walks/bookings/{id}).
 */
class WalkController extends Controller
{
    public function index(Request $request): View
    {
        $pet = $this->petFromQuery($request);
        $shelters = $this->shelters();

        $shelter = $shelters->firstWhere('slug', (string) $request->query('shelter'))
            ?? ($pet ? $shelters->firstWhere('id', $pet->shelter_id) : null)
            ?? $shelters->first();

        if ($pet && $shelter && $pet->shelter_id !== $shelter->id) {
            $pet = null; // сменили приют — собака из прежнего приюта не подходит
        }

        $now = now();
        $minWeek = WalkWeek::mondayOf($now);
        $maxWeek = WalkWeek::mondayOf(WalkSlot::horizonEndsAt($now));
        $week = WalkWeek::from($request->query('week'), $minWeek, $maxWeek);

        $slots = $shelter
            ? WalkSlot::query()
                ->where('shelter_id', $shelter->id)
                ->where('status', WalkSlotStatus::Open)
                ->where('starts_at', '>=', $week->start->copy()->utc())
                ->where('starts_at', '<', $week->end()->utc())
                ->where('starts_at', '<=', WalkSlot::horizonEndsAt($now))
                ->when($pet, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereHas('pets', fn (Builder $p) => $p->whereKey($pet->getKey()))
                    ->orDoesntHave('pets')))
                ->orderBy('starts_at')
                ->get()
            : collect();
        $slots->each->setRelation('shelter', $shelter);

        $days = $week->days($slots);
        $selected = (int) $request->query('slot', 0);
        $selectedDay = collect($days)->first(fn (array $day) => $day['slots']->contains('id', $selected))['key']
            ?? collect($days)->first(fn (array $day) => $day['available'])['key']
            ?? collect($days)->first(fn (array $day) => ! $day['past'])['key']
            ?? $days[0]['key'];

        $dogs = $shelter
            ? $shelter->pets()->visible()->walkable()->orderBy('name')->get(['id', 'name', 'slug', 'shelter_id'])
            : collect();

        return view('walks.index', [
            'shelters' => $shelters,
            'shelter' => $shelter,
            'pet' => $pet,
            'dogs' => $dogs,
            'week' => $week,
            'days' => $days,
            'canPrev' => $week->start->gt($minWeek),
            'canNext' => $week->start->lt($maxWeek),
            'selected' => $selected,
            'selectedDay' => $selectedDay,
        ]);
    }

    /** Кнопка «Продолжить» календаря: выбранный слот → шаг подтверждения. */
    public function continue(Request $request): RedirectResponse
    {
        $slot = WalkSlot::query()->find((int) $request->query('slot_id'));

        if (! $slot) {
            return back()->withErrors(['slot_id' => 'Выберите время прогулки.']);
        }

        return redirect()->route('walks.slots.show', array_filter([
            'slot' => $slot,
            'pet' => $request->query('pet') ?: null,
        ]));
    }

    public function show(Request $request, WalkSlot $slot, WalkBookingService $service): View|RedirectResponse
    {
        abort_unless(Gate::allows('view', $slot), 404);

        $user = $request->user();
        $slot->load(['shelter', 'pets' => fn ($q) => $q->visible()->walkable()->with('cover')->orderBy('name')]);

        if ($existing = $service->activeBookingIn($user, $slot)) {
            return redirect()->route('walks.bookings.show', $existing)
                ->with('info', 'Вы уже записаны на это время.');
        }

        $preselected = (string) $request->query('pet', '');
        $preselectedPet = $slot->pets->firstWhere('slug', $preselected);
        $taken = $service->takenPetIds($slot);

        return view('walks.show', [
            'slot' => $slot,
            'shelter' => $slot->shelter,
            'state' => $slot->state(),
            'canBook' => $user->can('book', $slot),
            'taken' => $taken,
            'selectedPetId' => $preselectedPet && ! in_array($preselectedPet->id, $taken, true) ? $preselectedPet->id : null,
            'noShowCount' => $noShows = $service->recentNoShows($user),
            'manyNoShows' => $noShows >= (int) config('walks.no_show_limit', 3),
            'activeCount' => $service->upcomingActive($user)->count(),
            'backUrl' => route('walks.index', array_filter([
                'shelter' => $slot->shelter->slug,
                'week' => WalkWeek::mondayOf($slot->starts_at)->format('Y-m-d'),
                'pet' => $preselectedPet?->slug,
                'slot' => $slot->id,
            ])),
            'cancelHours' => $service->hours('walks.cancel_before_hours'),
        ]);
    }

    public function book(WalkBookingRequest $request, WalkSlot $slot, WalkBookingService $service): RedirectResponse
    {
        $booking = $service->book($request->user(), $slot, $request->pet(), $request->validated('comment'));

        return redirect()->route('walks.bookings.show', $booking)
            ->with('status', 'Вы записаны на выгул')
            ->with('just_booked', true);
    }

    /**
     * Одобренные приюты, где ближайшие две недели есть прогулки.
     *
     * @return Collection<int, Shelter>
     */
    private function shelters()
    {
        return Shelter::query()->approved()
            ->whereHas('walkSlots', fn (Builder $q) => $q->upcoming()->where('starts_at', '<=', WalkSlot::horizonEndsAt()))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'city', 'address']);
    }

    private function petFromQuery(Request $request): ?Pet
    {
        $slug = (string) $request->query('pet', '');

        return $slug === '' ? null : Pet::query()->visible()->walkable()->where('slug', $slug)->first();
    }
}
