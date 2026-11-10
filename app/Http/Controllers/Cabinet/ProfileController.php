<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cabinet\PasswordRequest;
use App\Http\Requests\Cabinet\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Профиль (/cabinet/profile) — общий для всех ролей: контакты и смена пароля.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('cabinet.profile', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('cabinet.profile')->with('status', 'Профиль сохранён');
    }

    public function password(PasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return redirect()->route('cabinet.profile')->with('status', 'Пароль изменён');
    }
}
