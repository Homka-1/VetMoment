<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Services\WalkBookingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Обзор кабинета волонтёра (макет 09): ближайший выгул, заявки, пожертвования, избранное.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, WalkBookingService $walks): View
    {
        $user = $request->user();

        $nextWalk = $walks->upcomingActive($user)
            ->with(['slot.shelter', 'pet.cover'])
            ->get()
            ->sortBy(fn ($booking) => $booking->slot->starts_at)
            ->first();

        $paid = $user->donations()->where('status', PaymentStatus::Succeeded);
        $year = now()->startOfYear();

        return view('cabinet.dashboard', [
            'user' => $user,
            'nextWalk' => $nextWalk,
            'walksCount' => $walks->upcomingActive($user)->count(),
            'application' => $user->adoptionApplications()->with(['pet.cover', 'pet.shelter'])->latest()->latest('id')->first(),
            'applicationsActive' => $user->adoptionApplications()->whereIn('status', ApplicationStatus::active())->count(),
            'donationsTotal' => (int) (clone $paid)->where('paid_at', '>=', $year)->sum('amount'),
            'donationsCount' => (clone $paid)->where('paid_at', '>=', $year)->count(),
            'recentDonations' => (clone $paid)->with(['campaign', 'shelter'])->latest()->latest('id')->limit(2)->get(),
            'favoritesCount' => $user->favoritePets()->visible()->count(),
            'favorites' => $user->favoritePets()->visible()->with(['cover', 'shelter'])->orderByPivot('created_at', 'desc')->limit(4)->get(),
        ]);
    }
}
