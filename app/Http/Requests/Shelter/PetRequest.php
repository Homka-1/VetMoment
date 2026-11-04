<?php

namespace App\Http\Requests\Shelter;

use App\Enums\PetSize;
use App\Enums\PetStatus;
use App\Enums\Sex;
use App\Enums\Species;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Анкета питомца в кабинете приюта (docs/product/user-flows.md, 3; поля — data-requirements.md, 3).
 * Публичный статус — только у одобренного приюта (PetPolicy::publish) и только с фото.
 */
class PetRequest extends FormRequest
{
    public const MAX_PHOTOS = 10;

    /** Флажки формы: не отмеченный чекбокс не приходит — считаем его false. */
    public const FLAGS = [
        'birth_date_is_approx', 'good_with_kids', 'good_with_cats', 'good_with_dogs', 'house_trained',
        'leash_trained', 'vaccinated', 'sterilized', 'chipped', 'can_walk', 'guardianship_available',
    ];

    public function authorize(): bool
    {
        $pet = $this->route('pet');

        return $pet instanceof Pet
            ? $this->user()->can('update', $pet)
            : $this->user()->can('create', Pet::class);
    }

    protected function prepareForValidation(): void
    {
        $flags = [];
        foreach (self::FLAGS as $flag) {
            $flags[$flag] = $this->boolean($flag);
        }

        // Кнопки формы создания: «Сохранить черновик» / «Опубликовать».
        $status = match ($this->input('action')) {
            'draft' => PetStatus::Draft->value,
            'publish' => PetStatus::LookingForHome->value,
            default => $this->input('status'),
        };

        $this->merge($flags + [
            'name' => trim((string) $this->input('name')),
            'breed' => trim((string) $this->input('breed')) ?: null,
            'color' => trim((string) $this->input('color')) ?: null,
            'status' => $status,
            'photos' => is_array($this->input('photos')) ? $this->input('photos') : [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'species' => ['required', Rule::enum(Species::class)],
            'sex' => ['required', Rule::enum(Sex::class)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today', 'after:1990-01-01'],
            'size' => ['required', Rule::enum(PetSize::class)],
            'breed' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
            'character' => ['required', 'string', 'min:20', 'max:2000'],
            'health_notes' => ['nullable', 'string', 'max:2000'],
            'story' => ['nullable', 'string', 'max:5000'],
            'arrived_at' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(PetStatus::class)],
            ...array_fill_keys(self::FLAGS, ['boolean']),

            'photos_new' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos_new.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'photos' => ['array'],
            'photos.*.sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'photos.*.delete' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'string', 'regex:/^(existing|new):\d+$/'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $total = $this->photoCountAfterSave();

            if ($total > self::MAX_PHOTOS) {
                $validator->errors()->add('photos_new', 'У питомца может быть не больше '.self::MAX_PHOTOS.' фото — удалите лишние.');
            }

            $status = PetStatus::from($this->input('status'));

            if (! in_array($status, PetStatus::public(), true)) {
                return;
            }

            if (! $this->shelter()->isApproved()) {
                $validator->errors()->add('status', 'Опубликовать анкету можно после проверки приюта. Пока сохраните её как черновик.');
            } elseif ($total === 0) {
                $validator->errors()->add('photos_new', 'Добавьте хотя бы одно фото — без него анкету не опубликовать.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos_new.*.image' => 'Загрузите фото в формате JPG, PNG или WebP.',
            'photos_new.*.mimes' => 'Загрузите фото в формате JPG, PNG или WebP.',
            'photos_new.*.max' => 'Фото должно быть не больше 8 МБ.',
            'photos_new.*.uploaded' => 'Фото не загрузилось — возможно, файл больше 8 МБ.',
            'birth_date.after' => 'Проверьте дату рождения.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Кличка',
            'species' => 'Вид',
            'sex' => 'Пол',
            'birth_date' => 'Дата рождения',
            'size' => 'Размер',
            'breed' => 'Порода',
            'color' => 'Окрас',
            'character' => 'Характер',
            'health_notes' => 'Особенности здоровья',
            'story' => 'История',
            'arrived_at' => 'В приюте с',
            'status' => 'Статус',
            'photos_new' => 'Фото',
        ];
    }

    /**
     * Поля модели Pet.
     *
     * @return array<string, mixed>
     */
    public function petAttributes(): array
    {
        return $this->safe()->only([
            'name', 'species', 'sex', 'birth_date', 'size', 'breed', 'color', 'character', 'health_notes',
            'story', 'arrived_at', 'status', ...self::FLAGS,
        ]);
    }

    /**
     * Фото для PetEditor.
     *
     * @return array{new: list<UploadedFile>, existing: array<int|string, array{sort?: mixed, delete?: mixed}>, cover: string|null}
     */
    public function photoInput(): array
    {
        return [
            'new' => array_values(array_filter((array) $this->file('photos_new', []), fn ($f) => $f instanceof UploadedFile)),
            'existing' => (array) $this->input('photos', []),
            'cover' => $this->input('cover'),
        ];
    }

    public function shelter(): Shelter
    {
        $pet = $this->route('pet');

        return $pet instanceof Pet ? $pet->shelter : $this->user()->shelter();
    }

    private function photoCountAfterSave(): int
    {
        $pet = $this->route('pet');
        $kept = 0;

        if ($pet instanceof Pet) {
            $deleted = collect((array) $this->input('photos', []))
                ->filter(fn ($p) => is_array($p) && ! empty($p['delete']))
                ->keys()->map(fn ($id) => (int) $id);

            $kept = $pet->photos()->whereNotIn('id', $deleted)->count();
        }

        return $kept + count((array) $this->file('photos_new', []));
    }
}
