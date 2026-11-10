<?php

namespace App\Http\Controllers\Shelter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\ShelterProfileRequest;
use App\Services\ShelterRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Профиль приюта (/shelter/profile): описание, контакты, правила выгула, логотип. Меняет владелец приюта.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $shelter = $request->user()->shelter();
        Gate::authorize('update', $shelter);

        return view('shelter.profile', ['shelter' => $shelter]);
    }

    public function update(ShelterProfileRequest $request): RedirectResponse
    {
        $shelter = $request->user()->shelter();
        $data = $request->safe()->except(['logo', 'remove_logo']);
        $previous = $shelter->logo_path;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store(ShelterRegistration::LOGO_DIRECTORY, 'public');
        } elseif ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }

        $shelter->update($data);

        if ($previous && array_key_exists('logo_path', $data) && $data['logo_path'] !== $previous) {
            Storage::disk('public')->delete($previous);
        }

        return redirect()->route('shelter.profile')->with('status', 'Профиль приюта сохранён');
    }
}
