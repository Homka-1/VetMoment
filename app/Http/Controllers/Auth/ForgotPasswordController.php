<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Ответ одинаковый, есть такая почта или нет, — чтобы по форме нельзя было
     * проверить, зарегистрирован ли адрес. Ошибку показываем только при частых запросах.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
