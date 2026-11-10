<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PetStatus;
use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Модерация анкет: список и «Скрыть» / «Вернуть» (PetPolicy::hide). Скрытая анкета пропадает с сайта.
 */
class PetController extends Controller
{
    public const TABS = [
        'all' => 'Все',
        'hidden' => 'Скрытые',
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'all';
        $search = trim((string) $request->query('q'));

        $base = fn () => Pet::query()->where('status', '!=', PetStatus::Draft);

        $pets = $base()
            ->when($tab === 'hidden', fn ($q) => $q->where('is_hidden', true))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('name', 'like', '%'.mb_convert_case($search, MB_CASE_TITLE).'%')))
            ->with(['cover', 'shelter'])
            ->latest('updated_at')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.pets', [
            'pets' => $pets,
            'tab' => $tab,
            'search' => $search,
            'counts' => ['all' => $base()->count(), 'hidden' => $base()->where('is_hidden', true)->count()],
        ]);
    }

    public function hide(Pet $pet): RedirectResponse
    {
        Gate::authorize('hide', $pet);
        $pet->update(['is_hidden' => true]);

        return back()->with('status', "Анкета «{$pet->name}» скрыта с сайта");
    }

    public function restore(Pet $pet): RedirectResponse
    {
        Gate::authorize('hide', $pet);
        $pet->update(['is_hidden' => false]);

        return back()->with('status', "Анкета «{$pet->name}» возвращена на сайт");
    }
}
