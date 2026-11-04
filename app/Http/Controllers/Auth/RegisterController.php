<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['login', 'name', 'phone', 'email', 'password']),
            'role' => Role::Volunteer,
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute()))
            ->with('status', 'Добро пожаловать! Аккаунт создан.');
    }
}
