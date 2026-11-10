<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\AdoptionApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Заявки на питомцев — только просмотр (решения принимает приют, см. матрицу прав).
 */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status'));

        $applications = AdoptionApplication::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['pet.cover', 'pet.shelter', 'user'])
            ->latest()->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.applications', ['applications' => $applications, 'status' => $status]);
    }
}
