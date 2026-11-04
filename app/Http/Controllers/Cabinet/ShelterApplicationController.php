<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\ShelterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cabinet\ShelterApplicationRequest;
use App\Models\Shelter;
use App\Services\ShelterRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * «Стать приютом-партнёром»: новая заявка или исправление отклонённой.
 */
class ShelterApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $shelter = $request->user()->shelter();

        // У пользователя уже есть приют, и исправлять нечего — в кабинет приюта.
        if ($shelter !== null && $shelter->status !== ShelterStatus::Rejected) {
            return redirect()->route('shelter.dashboard');
        }

        $shelter === null
            ? Gate::authorize('register', Shelter::class)
            : Gate::authorize('resubmit', $shelter);

        return view('cabinet.shelter-application', ['shelter' => $shelter]);
    }

    public function store(ShelterApplicationRequest $request, ShelterRegistration $registration): RedirectResponse
    {
        // Права уже проверены в ShelterApplicationRequest::authorize().
        $user = $request->user();
        $shelter = $user->shelter();

        $data = $request->safe()->except(['logo', 'agree']);
        $logo = $request->file('logo');

        $shelter === null
            ? $registration->submit($user, $data, $logo)
            : $registration->resubmit($shelter, $data, $logo);

        return redirect()->route('shelter.dashboard')
            ->with('status', 'Заявка отправлена на модерацию. Пока её проверяют, можно готовить черновики анкет.');
    }
}
