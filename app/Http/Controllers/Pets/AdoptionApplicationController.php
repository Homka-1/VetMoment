<?php

namespace App\Http\Controllers\Pets;

use App\Enums\ApplicationType;
use App\Enums\PetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pets\AdoptionApplicationRequest;
use App\Models\AdoptionApplication;
use App\Models\Pet;
use App\Services\AdoptionApplications;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Заявка «Хочу забрать» / «Взять под опеку»: форма, отправка, отзыв.
 */
class AdoptionApplicationController extends Controller
{
    public function create(Request $request, Pet $pet): View
    {
        abort_unless(Gate::allows('view', $pet), 404);
        Gate::authorize('apply', $pet);

        $pet->load(['shelter', 'cover']);
        $user = $request->user();
        $type = ApplicationType::tryFrom((string) $request->query('type')) ?? ApplicationType::Adoption;

        if ($type === ApplicationType::Guardianship && ! $pet->guardianship_available) {
            $type = ApplicationType::Adoption;
        }

        return view('pets.apply', [
            'pet' => $pet,
            'type' => $type,
            'user' => $user,
            'phone' => Phone::format($user->phone),
            'reserved' => $pet->status === PetStatus::Reserved,
            'housing' => AdoptionApplicationRequest::HOUSING,
        ]);
    }

    public function store(AdoptionApplicationRequest $request, Pet $pet, AdoptionApplications $applications): RedirectResponse
    {
        $applications->submit($request->user(), $pet, $request->validated());

        return redirect()->route('pets.show', $pet)->with('status', 'Заявка отправлена. Приют свяжется с вами по телефону.');
    }

    public function withdraw(AdoptionApplication $application, AdoptionApplications $applications): RedirectResponse
    {
        Gate::authorize('withdraw', $application);

        $applications->withdraw($application);

        return back()->with('status', 'Заявка отозвана');
    }
}
