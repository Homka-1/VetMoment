<?php

namespace App\Services;

use App\Enums\PetSize;
use App\Enums\PetStatus;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Публичный каталог питомцев: фильтры, поиск по кличке, сортировка (docs/product/sitemap.md).
 * Запросы переносимы между PostgreSQL и SQLite: без ILIKE, возраст считается от дат в PHP.
 */
class PetCatalog
{
    public const PER_PAGE = 12;

    /** Возрастные группы фильтра → [от, до) лет. */
    public const AGES = [
        'baby' => ['label' => 'До года', 'from' => 0, 'to' => 1],
        'young' => ['label' => '1–3 года', 'from' => 1, 'to' => 3],
        'adult' => ['label' => '3–7 лет', 'from' => 3, 'to' => 7],
        'senior' => ['label' => 'Старше 7', 'from' => 7, 'to' => null],
    ];

    public const SORTS = [
        'waiting' => 'Дольше ждут',
        'new' => 'Новые',
    ];

    public const DEFAULT_SORT = 'waiting';

    /** Подписи фильтров «вид» и «пол» во множественном числе — как на чипах макета. */
    public const SPECIES_LABELS = ['dog' => 'Собаки', 'cat' => 'Кошки', 'other' => 'Другие'];

    public const SEX_LABELS = ['male' => 'Мальчик', 'female' => 'Девочка'];

    /**
     * @param  array<string, mixed>  $filters  из PetCatalogRequest::filters()
     */
    public function paginate(array $filters, ?Carbon $now = null): LengthAwarePaginator
    {
        $query = $this->query($filters, $now)->with(['cover', 'shelter']);
        $this->sort($query, $filters['sort'] ?? self::DEFAULT_SORT);

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Pet>
     */
    public function query(array $filters, ?Carbon $now = null): Builder
    {
        $now ??= now();
        $query = Pet::query()->inCatalog();

        if (($q = $filters['q'] ?? '') !== '') {
            $this->search($query, $q);
        }

        foreach (['species', 'sex', 'size'] as $column) {
            if (! empty($filters[$column])) {
                $query->whereIn($column, $filters[$column]);
            }
        }

        if (! empty($filters['age'])) {
            $query->where(function (Builder $q) use ($filters, $now): void {
                foreach ($filters['age'] as $key) {
                    $range = self::AGES[$key];
                    $q->orWhere(function (Builder $q) use ($range, $now): void {
                        // Родился позже, чем «сейчас минус верхняя граница», и не позже «сейчас минус нижняя».
                        $q->where('birth_date', '<=', $now->copy()->subYears($range['from'])->toDateString());
                        if ($range['to'] !== null) {
                            $q->where('birth_date', '>', $now->copy()->subYears($range['to'])->toDateString());
                        }
                    });
                }
            });
        }

        if (($city = $filters['city'] ?? '') !== '') {
            $query->whereHas('shelter', fn (Builder $q) => $q->where('city', $city));
        }

        if (($shelter = $filters['shelter'] ?? '') !== '') {
            $query->whereHas('shelter', fn (Builder $q) => $q->where('slug', $shelter));
        }

        if (! empty($filters['walk'])) {
            $query->where('can_walk', true)->whereIn('status', PetStatus::walkable());
        }

        if (! empty($filters['guardianship'])) {
            $query->where('guardianship_available', true);
        }

        return $query;
    }

    /** Сколько питомцев сейчас ищут дом — число рядом с заголовком каталога. */
    public function total(): int
    {
        return Pet::query()->inCatalog()->count();
    }

    /**
     * Города одобренных приютов, где есть питомцы в каталоге.
     *
     * @return Collection<int, string>
     */
    public function cities(): Collection
    {
        return Shelter::query()->approved()
            ->whereHas('pets', fn (Builder $q) => $q->inCatalog())
            ->orderBy('city')->distinct()->pluck('city');
    }

    /**
     * @return Collection<string, string> slug → «Название, город»
     */
    public function shelters(): Collection
    {
        return Shelter::query()->approved()
            ->whereHas('pets', fn (Builder $q) => $q->inCatalog())
            ->orderBy('name')->get(['slug', 'name', 'city'])
            ->mapWithKeys(fn (Shelter $s) => [$s->slug => "{$s->name}, {$s->city}"]);
    }

    /**
     * Активные фильтры — чипы над результатами: подпись → URL без этого значения.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array{label: string, href: string}>
     */
    public function activeChips(array $filters, string $baseUrl): array
    {
        $chips = [];
        $params = array_filter([
            'q' => $filters['q'] ?: null,
            'species' => $filters['species'] ?: null,
            'sex' => $filters['sex'] ?: null,
            'age' => $filters['age'] ?: null,
            'size' => $filters['size'] ?: null,
            'city' => $filters['city'] ?: null,
            'shelter' => $filters['shelter'] ?: null,
            'walk' => $filters['walk'] ? 1 : null,
            'guardianship' => $filters['guardianship'] ? 1 : null,
            'sort' => $filters['sort'] !== self::DEFAULT_SORT ? $filters['sort'] : null,
        ]);

        $without = function (string $key, ?string $value = null) use ($params, $baseUrl): string {
            if ($value === null) {
                unset($params[$key]);
            } else {
                $params[$key] = array_values(array_diff($params[$key], [$value]));
            }

            $query = http_build_query(array_filter($params));

            return $query === '' ? $baseUrl : $baseUrl.'?'.$query;
        };

        $labels = [
            'species' => self::SPECIES_LABELS,
            'sex' => self::SEX_LABELS,
            'age' => array_map(fn (array $a) => $a['label'], self::AGES),
            'size' => PetSize::options(),
        ];

        if ($params['q'] ?? null) {
            $chips[] = ['label' => '«'.$params['q'].'»', 'href' => $without('q')];
        }

        foreach ($labels as $key => $map) {
            foreach ($params[$key] ?? [] as $value) {
                $chips[] = ['label' => $map[$value], 'href' => $without($key, $value)];
            }
        }

        if ($params['city'] ?? null) {
            $chips[] = ['label' => $params['city'], 'href' => $without('city')];
        }

        if ($params['shelter'] ?? null) {
            $name = Shelter::query()->where('slug', $params['shelter'])->value('name');
            $chips[] = ['label' => $name ?? $params['shelter'], 'href' => $without('shelter')];
        }

        if ($params['walk'] ?? null) {
            $chips[] = ['label' => 'Готов к выгулу', 'href' => $without('walk')];
        }

        if ($params['guardianship'] ?? null) {
            $chips[] = ['label' => 'Можно под опеку', 'href' => $without('guardianship')];
        }

        return $chips;
    }

    /**
     * Поиск по кличке без учёта регистра. LOWER() в SQLite понимает только латиницу,
     * поэтому дополнительно ищем вариант «С заглавной» — так кириллица находится и на SQLite.
     *
     * @param  Builder<Pet>  $query
     */
    private function search(Builder $query, string $q): void
    {
        $escape = fn (string $s): string => '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s).'%';
        $lower = mb_strtolower($q);
        $title = mb_convert_case($lower, MB_CASE_TITLE);

        $query->where(function (Builder $w) use ($escape, $lower, $title): void {
            $w->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$escape($lower)])
                ->orWhereRaw("name LIKE ? ESCAPE '\\'", [$escape($title)]);
        });
    }

    /**
     * @param  Builder<Pet>  $query
     */
    private function sort(Builder $query, string $sort): void
    {
        if ($sort === 'new') {
            $query->latest('created_at')->latest('id');

            return;
        }

        // «Дольше всех ждут»: раньше поступившие сверху, без даты поступления — в конце.
        $query->orderByRaw('CASE WHEN arrived_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('arrived_at')
            ->orderBy('id');
    }
}
