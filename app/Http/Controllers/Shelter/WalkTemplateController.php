<?php

namespace App\Http\Controllers\Shelter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\WalkTemplateRequest;
use App\Models\WalkSlot;
use App\Models\WalkTemplate;
use App\Services\WalkSlots;
use App\Support\Plural;
use App\Support\WalkWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Шаблоны недели (/shelter/slots/templates): дни × время × места × собаки → слоты на период до 8 недель.
 */
class WalkTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $shelter = $request->user()->shelter();

        return view('shelter.slots.templates', [
            'shelter' => $shelter,
            'templates' => $shelter->walkTemplates()->with('pets')
                ->withCount(['slots as upcoming_count' => fn ($q) => $q->where('starts_at', '>', now())->where('status', 'open')])
                ->latest()->get(),
            'dogs' => $shelter->pets()->walkable()->orderBy('name')->get(['id', 'name']),
            'canCreate' => $request->user()->can('create', WalkSlot::class),
            'durations' => WalkTemplateRequest::durations(),
        ]);
    }

    public function store(WalkTemplateRequest $request, WalkSlots $slots): RedirectResponse
    {
        $data = $request->templateData();
        $result = $slots->createFromTemplate($request->user()->shelter(), $data);

        $message = 'Шаблон сохранён: '.Plural::format($result['created'], ['слот создан', 'слота создано', 'слотов создано']);
        if ($result['skipped'] > 0) {
            $message .= ', '.Plural::format($result['skipped'], ['пропущен', 'пропущено', 'пропущено']).' — на это время слот уже был';
        }

        return redirect()->route('shelter.slots.index', ['week' => WalkWeek::mondayOf($data['valid_from'])->format('Y-m-d')])
            ->with('status', $message);
    }

    public function destroy(WalkTemplate $template, WalkSlots $slots): RedirectResponse
    {
        Gate::authorize('delete', $template);

        $removed = $slots->deleteTemplate($template);

        return redirect()->route('shelter.slot-templates.index')->with('status', $removed > 0
            ? 'Шаблон удалён вместе с '.Plural::format($removed, ['будущим слотом', 'будущими слотами', 'будущими слотами']).' без записей'
            : 'Шаблон удалён');
    }
}
