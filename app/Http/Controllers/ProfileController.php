<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->merge([
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);

        $request->validate(
            [
                'login' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
                'phone' => [
                    'required',
                    'string',
                    'max:16',
                    'regex:/^\+[1-9]\d{9,14}$/',
                    Rule::unique('users')->ignore($user->id),
                ],
                'password' => ['nullable', 'string', 'min:4'],
            ],
            [
                'phone.regex' => 'Введите номер телефона в международном формате. Пример: +79991234567',
            ]
        );

        $user->login = $request->login;
        $user->phone = $request->phone;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Профиль успешно обновлен!');
    }

    private function normalizePhone(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        return '+' . ltrim($digits, '+');
    }
}
