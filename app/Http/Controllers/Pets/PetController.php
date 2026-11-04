<?php

namespace App\Http\Controllers\Pets;

use App\Enums\ApplicationType;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pets\PetCatalogRequest;
use App\Models\Pet;
use App\Models\WalkSlot;
use App\Services\PetCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Публичные страницы питомцев: каталог /pets и карточка /pets/{slug}.
 */
class PetController extends Controller
{
    /** Сколько ближайших слотов выгула и похожих питомцев показывать на карточке. */
    private const SLOTS_ON_CARD = 4;

    private const SIMILAR = 4;

    public function index(PetCatalogRequest $request, PetCatalog $catalog): View
    {
        $filters = $request->filters();
        $pets = $catalog->paginate($filters);

        return view('pets.index', [
            'pets' => $pets,
            'filters' => $filters,
            'total' => $catalog->total(),
            'cities' => $catalog->cities(),
            'shelters' => $catalog->shelters(),
            'chips' => $catalog->activeChips($filters, route('pets.index')),
            'favoriteIds' => $this->favoriteIds($request, $pets->pluck('id')->all()),
        ]);
    }

    public function show(Request $request, Pet $pet): View
    {
        // Чужие черновики, архив и скрытые анкеты — «не найдено», а не «доступ запрещён».
        abort_unless(Gate::allows('view', $pet), 404);

        $pet->load(['shelter', 'photos']);
        $user = $request->user();

        $campaign = $pet->campaigns()->visible()->where('status', CampaignStatus::Active)->latest('starts_at')->first();

        $slots = $pet->isWalkable()
            ? WalkSlot::query()->upcoming()
                ->where('shelter_id', $pet->shelter_id)
                ->where('starts_at', '<=', now()->addDays((int) config('walks.horizon_days', 14)))
                ->where(fn (Builder $q) => $q->whereHas('pets', fn (Builder $p) => $p->whereKey($pet->getKey()))->orDoesntHave('pets'))
                ->orderBy('starts_at')->limit(self::SLOTS_ON_CARD)->get()
            : collect();

        $similar = Pet::query()->inCatalog()
            ->whereKeyNot($pet->getKey())
            ->where('species', $pet->species)
            ->with(['cover', 'shelter'])
            ->orderByRaw('CASE WHEN shelter_id = ? THEN 0 ELSE 1 END', [$pet->shelter_id])
            ->orderByRaw('CASE WHEN arrived_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('arrived_at')
            ->limit(self::SIMILAR)->get();

        $applications = $user
            ? $pet->applications()->active()->where('user_id', $user->getKey())->get()->keyBy(fn ($a) => $a->type->value)
            : collect();

        return view('pets.show', [
            'pet' => $pet,
            'campaign' => $campaign,
            'slots' => $slots,
            'similar' => $similar,
            'applications' => $applications,
            'canApply' => $user === null || $user->can('apply', $pet),
            'canManage' => $user?->can('update', $pet) ?? false,
            'favorited' => $user !== null && $user->favoritePets()->whereKey($pet->getKey())->exists(),
            'favoriteIds' => $this->favoriteIds($request, $similar->pluck('id')->all()),
            'adoptionType' => ApplicationType::Adoption->value,
        ]);
    }

    /**
     * Какие из показанных питомцев у пользователя в избранном — одним запросом.
     *
     * @param  list<int>  $petIds
     * @return list<int>
     */
    private function favoriteIds(Request $request, array $petIds): array
    {
        $user = $request->user();

        if ($user === null || $petIds === []) {
            return [];
        }

        return $user->favoritePets()->whereIn('pets.id', $petIds)->pluck('pets.id')->map(fn ($id) => (int) $id)->all();
    }
}
