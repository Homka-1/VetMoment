<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Временные стартовые страницы кабинетов: нужны, чтобы после входа было куда попасть
 * и чтобы проверять доступ по ролям. Настоящие кабинеты — этап 11.
 */
class CabinetStubController extends Controller
{
    public function volunteer(Request $request): View
    {
        return view('cabinet.stub', ['title' => 'Кабинет волонтёра', 'user' => $request->user()]);
    }

    public function shelter(Request $request): View
    {
        return view('cabinet.stub', [
            'title' => 'Кабинет приюта',
            'user' => $request->user(),
            'shelter' => $request->user()->shelter(),
        ]);
    }

    public function admin(Request $request): View
    {
        return view('cabinet.stub', ['title' => 'Админка', 'user' => $request->user()]);
    }
}
