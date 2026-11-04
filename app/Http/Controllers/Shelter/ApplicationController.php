<?php

namespace App\Http\Controllers\Shelter;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\ApplicationDecisionRequest;
use App\Models\AdoptionApplication;
use App\Services\AdoptionApplications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Входящие заявки на питомцев приюта (/shelter/applications): список и решение по заявке.
 */
class ApplicationController extends Controller
{
    public const TABS = [
        'new' => ['label' => 'Новые', 'statuses' => [ApplicationStatus::New]],
        'in_review' => ['label' => 'В работе', 'statuses' => [ApplicationStatus::InReview, ApplicationStatus::Approved]],
        'closed' => ['label' => 'Закрытые', 'statuses' => [ApplicationStatus::Completed, ApplicationStatus::Rejected, ApplicationStatus::Withdrawn]],
    ];

    /** Тексты кнопок и тостов: кнопка и результат называются одинаково. */
    public const ACTIONS = [
        'in_review' => ['button' => 'Взять в работу', 'done' => 'Заявка взята в работу'],
        'approved' => ['button' => 'Одобрить', 'done' => 'Заявка одобрена'],
        'completed' => ['button' => 'Питомец передан', 'done' => 'Заявка завершена — питомец нашёл дом'],
        'rejected' => ['button' => 'Отклонить', 'done' => 'Заявка отклонена'],
    ];

    public function index(Request $request): View
    {
        $shelter = $request->user()->shelter();
        $tab = array_key_exists((string) $request->query('status'), self::TABS) ? (string) $request->query('status') : 'new';

        $base = AdoptionApplication::query()->whereHas('pet', fn (Builder $q) => $q->where('shelter_id', $shelter->getKey()));

        $counts = [];
        foreach (self::TABS as $key => $definition) {
            $counts[$key] = (clone $base)->whereIn('status', $definition['statuses'])->count();
        }

        $applications = (clone $base)
            ->whereIn('status', self::TABS[$tab]['statuses'])
            ->with(['pet.cover'])
            ->latest()->latest('id')
            ->paginate(20)->withQueryString();

        return view('shelter.applications.index', compact('applications', 'tab', 'counts'));
    }

    public function show(AdoptionApplication $application, AdoptionApplications $service): View
    {
        Gate::authorize('process', $application);

        $application->load(['pet.cover', 'pet.shelter', 'user']);

        return view('shelter.applications.show', [
            'application' => $application,
            'next' => $service->nextStatuses($application),
        ]);
    }

    public function update(ApplicationDecisionRequest $request, AdoptionApplication $application, AdoptionApplications $service): RedirectResponse
    {
        $status = $request->status();
        $service->process($application, $status, $request->validated('shelter_comment'));

        return redirect()->route('shelter.applications.show', $application)
            ->with('status', self::ACTIONS[$status->value]['done']);
    }
}
