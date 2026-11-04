<?php

namespace App\Http\Requests\Pets;

use App\Enums\PetSize;
use App\Enums\Sex;
use App\Enums\Species;
use App\Services\PetCatalog;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Фильтры публичного каталога (GET /pets). Ссылки с фильтрами живут в закладках и поисковиках,
 * поэтому неизвестные значения не дают ошибку, а молча отбрасываются в prepareForValidation().
 */
class PetCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $list = fn (string $key, array $allowed): array => array_values(array_unique(array_intersect(
            array_map('strval', array_filter((array) $this->query($key, []), 'is_scalar')),
            $allowed,
        )));
        $text = fn (string $key, int $max): string => mb_substr(trim((string) (is_scalar($this->query($key)) ? $this->query($key) : '')), 0, $max);

        $this->replace([
            'q' => $text('q', 50),
            'species' => $list('species', Species::values()),
            'sex' => $list('sex', [Sex::Male->value, Sex::Female->value]),
            'age' => $list('age', array_keys(PetCatalog::AGES)),
            'size' => $list('size', PetSize::values()),
            'city' => $text('city', 100),
            'shelter' => $text('shelter', 255),
            'walk' => $this->boolean('walk'),
            'guardianship' => $this->boolean('guardianship'),
            'sort' => array_key_exists((string) $this->query('sort'), PetCatalog::SORTS) ? (string) $this->query('sort') : PetCatalog::DEFAULT_SORT,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['string'],
            'species' => ['array'],
            'sex' => ['array'],
            'age' => ['array'],
            'size' => ['array'],
            'city' => ['string'],
            'shelter' => ['string'],
            'walk' => ['boolean'],
            'guardianship' => ['boolean'],
            'sort' => ['string'],
        ];
    }

    /**
     * Очищенные фильтры для PetCatalog.
     *
     * @return array{q: string, species: list<string>, sex: list<string>, age: list<string>, size: list<string>, city: string, shelter: string, walk: bool, guardianship: bool, sort: string}
     */
    public function filters(): array
    {
        /** @var array{q: string, species: list<string>, sex: list<string>, age: list<string>, size: list<string>, city: string, shelter: string, walk: bool, guardianship: bool, sort: string} */
        return $this->all();
    }
}
