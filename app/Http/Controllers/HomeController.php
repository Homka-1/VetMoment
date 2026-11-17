<?php

namespace App\Http\Controllers;

use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkSlot;
use App\Services\PlatformStats;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Главная (макет 01): герой, три пути помощи и блоки с данными — питомцы, сборы, выгулы недели, приюты, цифры.
 *
 * Кэш (этап 13): цифры проекта и приюты-партнёры — агрегаты по всем таблицам, меняются медленно → 10 минут.
 * Питомцы, сборы и слоты не кэшируются: свободные места и прогресс сбора должны быть точными.
 */
class HomeController extends Controller
{
    public const CACHE_TTL_SECONDS = 600;

    public const CACHE_KEYS = ['home.stats', 'home.shelters'];

    public function __invoke(PlatformStats $stats): View
    {
        $weekEnds = now()->addDays(7);

        return view('home', [
            'pets' => Pet::query()->inCatalog()
                ->with(['cover', 'shelter'])
                ->orderByRaw('CASE WHEN arrived_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('arrived_at')->orderBy('id')
                ->limit(4)->get(),
            'campaigns' => DonationCampaign::query()->visible()->acceptingDonations()
                ->with(['shelter', 'pet.cover'])
                ->orderByRaw('CASE WHEN ends_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('ends_at')->orderBy('id')
                ->limit(3)->get(),
            'slots' => WalkSlot::query()->upcoming()
                ->where('starts_at', '<=', $weekEnds)
                ->whereHas('shelter', fn ($q) => $q->approved())
                ->with('shelter')
                ->limit(7)->get(),
            'shelters' => $this->shelters(),
            'stats' => Cache::remember('home.stats', self::CACHE_TTL_SECONDS, fn () => $stats->overall()),
        ]);
    }

    /**
     * Приюты-партнёры с числом питомцев. В кэше — только «id → число» (кэш Laravel не хранит объекты моделей,
     * cache.serializable_classes = false), сами приюты — один дешёвый запрос по ключам.
     *
     * @return Collection<int, Shelter>
     */
    private function shelters(): Collection
    {
        $counts = Cache::remember('home.shelters', self::CACHE_TTL_SECONDS, fn () => Shelter::query()->approved()
            ->withCount(['pets' => fn ($q) => $q->visible()])
            ->orderByDesc('pets_count')->orderBy('id')
            ->limit(4)->pluck('pets_count', 'id')->all());

        $shelters = Shelter::query()->approved()->whereKey(array_keys($counts))->get()->keyBy('id');

        return new Collection(collect($counts)
            ->filter(fn ($count, $id) => $shelters->has($id))
            ->map(fn ($count, $id) => $shelters[$id]->setAttribute('pets_count', $count))
            ->values()
            ->all());
    }
}
