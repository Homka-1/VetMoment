<?php

namespace App\Http\Controllers\Shelter;

use App\Enums\PetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shelter\PetRequest;
use App\Models\Pet;
use App\Services\PetEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Анкеты питомцев в кабинете приюта (/shelter/pets): список, создание, редактирование, архив.
 */
class PetController extends Controller
{
    /** Фильтры-чипы над таблицей → статусы. */
    public const TABS = [
        'all' => ['label' => 'Все', 'statuses' => null],
        'looking' => ['label' => 'Ищут дом', 'statuses' => [PetStatus::LookingForHome]],
        'treatment' => ['label' => 'На лечении', 'statuses' => [PetStatus::OnTreatment]],
        'reserved' => ['label' => 'Забронированы', 'statuses' => [PetStatus::Reserved]],
        'draft' => ['label' => 'Черновики', 'statuses' => [PetStatus::Draft]],
        'adopted' => ['label' => 'Нашли дом', 'statuses' => [PetStatus::Adopted]],
        'archived' => ['label' => 'Архив', 'statuses' => [PetStatus::Archived]],
    ];

    public function index(Request $request): View
    {
        $shelter = $request->user()->shelter();
        $tab = array_key_exists((string) $request->query('status'), self::TABS) ? (string) $request->query('status') : 'all';
        $q = mb_substr(trim((string) $request->query('q')), 0, 50);

        $counts = $shelter->pets()->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $pets = $shelter->pets()
            ->when(self::TABS[$tab]['statuses'], fn (Builder $query, array $statuses) => $query->whereIn('status', $statuses))
            // В кабинете «Все» — без архива, архив — отдельной вкладкой.
            ->when($tab === 'all', fn (Builder $query) => $query->where('status', '!=', PetStatus::Archived))
            ->when($q !== '', function (Builder $query) use ($q): void {
                $lower = mb_strtolower($q);
                $query->where(fn (Builder $w) => $w
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$lower.'%'])
                    ->orWhere('name', 'like', '%'.mb_convert_case($lower, MB_CASE_TITLE).'%'));
            })
            ->with('cover')
            ->withCount(['applications as active_applications_count' => fn (Builder $query) => $query->active()])
            ->latest('updated_at')->latest('id')
            ->paginate(20)->withQueryString();

        $tabs = [];
        foreach (self::TABS as $key => $definition) {
            $count = $definition['statuses'] === null
                ? $counts->except(PetStatus::Archived->value)->sum()
                : collect($definition['statuses'])->sum(fn (PetStatus $s) => (int) ($counts[$s->value] ?? 0));

            if ($count > 0 || in_array($key, ['all', $tab], true)) {
                $tabs[$key] = ['label' => $definition['label'], 'count' => (int) $count];
            }
        }

        return view('shelter.pets.index', compact('shelter', 'pets', 'tabs', 'tab', 'q'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Pet::class);

        return view('shelter.pets.form', [
            'pet' => new Pet(['status' => PetStatus::Draft, 'size' => null]),
            'shelter' => $request->user()->shelter(),
        ]);
    }

    public function store(PetRequest $request, PetEditor $editor): RedirectResponse
    {
        $pet = $editor->create($request->shelter(), $request->petAttributes(), $request->photoInput());

        return redirect()->route('shelter.pets.index')->with('status', $pet->status === PetStatus::Draft
            ? "Черновик «{$pet->name}» сохранён"
            : "Анкета «{$pet->name}» опубликована");
    }

    public function edit(Pet $pet): View
    {
        Gate::authorize('update', $pet);

        $pet->load(['shelter', 'photos']);

        return view('shelter.pets.form', ['pet' => $pet, 'shelter' => $pet->shelter]);
    }

    public function update(PetRequest $request, Pet $pet, PetEditor $editor): RedirectResponse
    {
        $wasPublic = in_array($pet->status, PetStatus::public(), true);
        $pet = $editor->update($pet, $request->petAttributes(), $request->photoInput());
        $isPublic = in_array($pet->status, PetStatus::public(), true);

        return redirect()->route('shelter.pets.edit', $pet)->with('status', ! $wasPublic && $isPublic
            ? "Анкета «{$pet->name}» опубликована"
            : 'Изменения сохранены');
    }

    public function destroy(Pet $pet, PetEditor $editor): RedirectResponse
    {
        Gate::authorize('delete', $pet);

        $editor->archive($pet);

        return redirect()->route('shelter.pets.index')->with('status', "Анкета «{$pet->name}» перенесена в архив");
    }
}
