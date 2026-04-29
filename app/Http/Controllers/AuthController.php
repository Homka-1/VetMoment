<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge([
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);

        $request->validate(
            [
                'login' => 'required|string|max:255|unique:users,login',
                'phone' => [
                    'required',
                    'string',
                    'max:16',
                    'regex:/^\+[1-9]\d{9,14}$/',
                    'unique:users,phone',
                ],
                'password' => 'required|string|min:4',
            ],
            [
                'phone.regex' => 'Введите номер телефона в международном формате. Пример: +79991234567',
                'phone.unique' => 'Пользователь с таким номером телефона уже зарегистрирован.',
            ]
        );

        $user = User::create([
            'login' => $request->login,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('home');
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

        // РФ/КЗ часто вводят: 8XXXXXXXXXX или 9XXXXXXXXX (без кода страны)
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        // Приводим к E.164: + и 10-15 цифр (первая цифра не 0)
        return '+' . ltrim($digits, '+');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'login' => 'Неверный логин или пароль.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
