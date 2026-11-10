<?php

namespace App\Http\Controllers\Shelter;

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PetStatus;
use App\Http\Controllers\Controller;
use App\Models\AdoptionApplication;
use App\Models\WalkBooking;
use App\Services\WalkBookingService;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Сводка кабинета приюта (макет 10): цифры, записи на сегодня, новые заявки.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, WalkBookingService $walks): View
    {
        $user = $request->user();
        $shelter = $user->shelter();

        $dayStart = LocalTime::of(now())->startOfDay()->utc();
        $dayEnd = LocalTime::of(now())->endOfDay()->utc();
        $monthStart = LocalTime::of(now())->startOfMonth()->utc();

        $today = fn () => WalkBooking::query()
            ->whereHas('slot', fn (Builder $q) => $q->where('shelter_id', $shelter->id)->whereBetween('starts_at', [$dayStart, $dayEnd]));

        $todayBookings = $today()
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::NoShow])
            ->with(['slot', 'user', 'pet'])
            ->get()
            ->sortBy(fn (WalkBooking $b) => $b->slot->starts_at->timestamp)
            ->values();

        $applications = fn () => AdoptionApplication::query()->whereHas('pet', fn (Builder $q) => $q->where('shelter_id', $shelter->id));
        $pets = fn () => $shelter->pets();

        $activeCampaigns = $shelter->campaigns()->where('status', CampaignStatus::Active)->count();

        return view('shelter.dashboard', [
            'user' => $user,
            'shelter' => $shelter,
            'lookingForHome' => $pets()->where('status', PetStatus::LookingForHome)->count(),
            'onTreatment' => $pets()->where('status', PetStatus::OnTreatment)->count(),
            'newApplications' => $applications()->where('status', ApplicationStatus::New)->count(),
            'walksToday' => $today()->whereIn('status', BookingStatus::active())->count(),
            'walksPending' => $today()->where('status', BookingStatus::Pending)->count(),
            'raisedMonth' => (int) $shelter->donations()->where('status', PaymentStatus::Succeeded)->where('paid_at', '>=', $monthStart)->sum('amount'),
            'activeCampaigns' => $activeCampaigns,
            'todayBookings' => $todayBookings,
            'next' => $todayBookings->mapWithKeys(fn (WalkBooking $b) => [$b->id => $walks->nextStatuses($b)]),
            'noShows' => $todayBookings->pluck('user')->unique('id')->mapWithKeys(fn ($u) => [$u->id => $walks->hasManyNoShows($u)]),
            'applications' => $applications()->where('status', ApplicationStatus::New)->with('pet.cover')->latest()->limit(5)->get(),
        ]);
    }
}
