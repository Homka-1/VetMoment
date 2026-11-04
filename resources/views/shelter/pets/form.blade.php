@use('App\Enums\PetSize')
@use('App\Enums\PetStatus')
@use('App\Enums\Sex')
@use('App\Enums\Species')
@use('App\Http\Requests\Shelter\PetRequest')
@use('App\Models\Pet')
{{-- Анкета питомца в кабинете приюта: три блока полей (user-flows, 3), фото, статус.
     Создание — кнопки «Сохранить черновик» / «Опубликовать»; редактирование — выбор статуса. --}}
@php
    $editing = $pet->exists;
    $title = $editing ? $pet->name : 'Новый питомец';
    $approved = $shelter->isApproved();
    $photos = $editing ? $pet->photos : collect();
    $freeSlots = PetRequest::MAX_PHOTOS - $photos->count();
    $statusOptions = collect(PetStatus::cases())
        ->reject(fn (PetStatus $s) => ! $approved && in_array($s, PetStatus::public(), true) && $s !== $pet->status)
        ->mapWithKeys(fn (PetStatus $s) => [$s->value => $s->label()])->all();
    $date = fn ($value) => $value?->format('Y-m-d');
@endphp
<x-layouts.cabinet :title="$title" :heading="$editing ? 'Анкета: '.$pet->name : 'Новый питомец'">
    <x-slot:actions>
        @if ($editing && in_array($pet->status, PetStatus::public(), true))
            <x-button variant="secondary" icon="paw" :href="route('pets.show', $pet)">Открыть на сайте</x-button>
        @endif
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6">
        <x-breadcrumbs :items="['Питомцы' => route('shelter.pets.index'), $title => null]" />
        <x-shelter-moderation :shelter="$shelter" />
        <x-form-errors />

        <form method="POST" action="{{ $editing ? route('shelter.pets.update', $pet) : route('shelter.pets.store') }}"
              enctype="multipart/form-data" class="grid gap-6" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card padding="lg" as="section" aria-labelledby="pet-form-basic" class="grid gap-6">
                <h2 id="pet-form-basic" class="type-h4">Основное</h2>
                <div class="grid gap-6 md:grid-cols-2">
                    <x-input name="name" label="Кличка" :value="$pet->name" required maxlength="50" />
                    <x-select name="species" label="Вид" :options="Species::options()" :value="$pet->species" placeholder="Выберите" required />
                    <x-select name="sex" label="Пол" :options="Sex::options()" :value="$pet->sex" placeholder="Выберите" required />
                    <x-select name="size" label="Размер" :options="[
                        PetSize::Small->value => 'Маленький — до 10 кг',
                        PetSize::Medium->value => 'Средний — 10–25 кг',
                        PetSize::Large->value => 'Крупный — больше 25 кг',
                    ]" :value="$pet->size" placeholder="Выберите" required />
                    <div class="grid content-start gap-2">
                        <x-input name="birth_date" type="date" label="Дата рождения" :value="$date($pet->birth_date)" :max="now()->format('Y-m-d')"
                                 hint="Если точной нет — укажите примерную" />
                        <x-checkbox name="birth_date_is_approx" :checked="$pet->birth_date_is_approx">Дата примерная</x-checkbox>
                    </div>
                    <x-input name="arrived_at" type="date" label="В приюте с" :value="$date($pet->arrived_at)" :max="now()->format('Y-m-d')"
                             hint="По ней каталог сортирует «Дольше ждут»" />
                    <x-input name="breed" label="Порода" :value="$pet->breed" maxlength="100" placeholder="Метис" />
                    <x-input name="color" label="Окрас" :value="$pet->color" maxlength="100" />
                </div>
            </x-card>

            <x-card padding="lg" as="section" aria-labelledby="pet-form-character" class="grid gap-6">
                <h2 id="pet-form-character" class="type-h4">Характер и здоровье</h2>
                <x-textarea name="character" label="Характер" :value="$pet->character" required rows="4" maxlength="2000"
                            hint="Каким питомец бывает дома и на прогулке. От 20 символов" />
                <div class="grid gap-6 md:grid-cols-2">
                    <fieldset class="grid content-start gap-1">
                        <legend class="type-label mb-2">Ладит и умеет</legend>
                        @foreach (Pet::TRAITS as $key => $label)
                            <x-checkbox :name="$key" :checked="$pet->{$key}">{{ $label }}</x-checkbox>
                        @endforeach
                    </fieldset>
                    <fieldset class="grid content-start gap-1">
                        <legend class="type-label mb-2">Здоровье</legend>
                        <x-checkbox name="vaccinated" :checked="$pet->vaccinated">Привит</x-checkbox>
                        <x-checkbox name="sterilized" :checked="$pet->sterilized">Стерилизован или кастрирован</x-checkbox>
                        <x-checkbox name="chipped" :checked="$pet->chipped">Чипирован</x-checkbox>
                    </fieldset>
                </div>
                <x-textarea name="health_notes" label="Особенности здоровья" :value="$pet->health_notes" rows="3" maxlength="2000"
                            hint="Диета, лекарства, ограничения на прогулках" />
            </x-card>

            <x-card padding="lg" as="section" aria-labelledby="pet-form-story" class="grid gap-6">
                <h2 id="pet-form-story" class="type-h4">История и помощь</h2>
                <x-textarea name="story" label="История" :value="$pet->story" rows="5" maxlength="5000"
                            hint="Как питомец попал в приют и что с ним было дальше" />
                <div class="grid gap-2">
                    <x-toggle name="can_walk" :checked="$pet->can_walk">Можно гулять с волонтёрами</x-toggle>
                    <x-toggle name="guardianship_available" :checked="$pet->guardianship_available">Можно взять под опеку</x-toggle>
                </div>
            </x-card>

            <x-card padding="lg" as="section" aria-labelledby="pet-form-photos" class="grid gap-5" data-photo-manager data-photo-max="{{ PetRequest::MAX_PHOTOS }}">
                <div>
                    <h2 id="pet-form-photos" class="type-h4">Фото</h2>
                    <p class="mt-1 text-sm text-secondary">
                        До {{ PetRequest::MAX_PHOTOS }} фото в JPG, PNG или WebP, до 8 МБ каждое. Обложка видна в каталоге;
                        без выбора обложкой станет первое фото. <span data-photo-js hidden>Порядок меняется перетаскиванием или стрелками.</span>
                    </p>
                </div>

                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4" data-photo-list aria-label="Загруженные фото">
                    @foreach ($photos as $photo)
                        <li class="grid gap-2 rounded-md border border-subtle bg-white p-2" data-photo-item draggable="false">
                            <div class="relative aspect-[4/3] overflow-hidden rounded-sm bg-ink-100">
                                <img src="{{ $photo->thumbUrl() }}" alt="{{ $pet->photoAlt($loop->iteration) }}" loading="lazy" class="size-full object-cover" draggable="false">
                            </div>
                            <x-radio name="cover" value="existing:{{ $photo->id }}" :checked="$photo->is_cover" class="text-sm">Обложка</x-radio>
                            <div class="flex items-center justify-between gap-2" data-photo-sort-wrap>
                                <label for="f-photo-sort-{{ $photo->id }}" class="text-sm text-secondary">Порядок</label>
                                <input type="number" min="0" max="999" id="f-photo-sort-{{ $photo->id }}" name="photos[{{ $photo->id }}][sort]" value="{{ $photo->sort }}"
                                       class="h-control-sm w-20 rounded-sm border-control border-strong px-2 text-sm" data-photo-sort>
                            </div>
                            <div class="hidden items-center justify-between gap-2" data-photo-move-wrap>
                                <x-button variant="ghost" size="sm" icon="chevron-left" icon-only type="button" data-photo-move="-1" label="Сдвинуть раньше" />
                                <x-button variant="ghost" size="sm" icon="chevron-right" icon-only type="button" data-photo-move="1" label="Сдвинуть позже" />
                            </div>
                            <x-checkbox name="photos[{{ $photo->id }}][delete]" id="f-photo-delete-{{ $photo->id }}" class="text-sm">Удалить</x-checkbox>
                        </li>
                    @endforeach
                </ul>

                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4" data-photo-new aria-label="Новые фото" hidden></ul>

                <x-form.field label="Добавить фото" for="f-photos_new" :error="$errors->first('photos_new') ?: collect($errors->get('photos_new.*'))->flatten()->first()"
                              :hint="$freeSlots > 0 ? 'Можно добавить ещё '.$freeSlots : 'Достигнут предел — удалите лишние фото, чтобы добавить новые'">
                    <label for="f-photos_new" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed border-ink-300 bg-canvas px-4 py-8 text-center transition-colors hover:border-ink-900
                                  has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus">
                        <x-icon name="upload" size="lg" class="text-ink-700" />
                        <span class="font-semibold">Выберите файлы</span>
                        <span class="text-sm text-secondary">или перетащите их сюда</span>
                        <input type="file" name="photos_new[]" id="f-photos_new" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-input
                               @if ($errors->has('photos_new') || $errors->has('photos_new.*')) aria-invalid="true" aria-describedby="f-photos_new-error" @endif>
                    </label>
                </x-form.field>
            </x-card>

            @if ($editing)
                <x-card padding="lg" as="section" aria-labelledby="pet-form-status" class="grid gap-4">
                    <h2 id="pet-form-status" class="type-h4">Статус</h2>
                    <x-select name="status" label="Статус анкеты" :options="$statusOptions" :value="$pet->status" class="md:max-w-sm"
                              :hint="$approved ? '«Нашёл дом» закроет открытые заявки на питомца' : 'Опубликовать анкету можно после проверки приюта'" />
                </x-card>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                @if ($editing)
                    <x-button size="lg">Сохранить</x-button>
                @else
                    <x-button size="lg" name="action" value="publish" :disabled="! $approved">Опубликовать</x-button>
                    <x-button size="lg" variant="secondary" name="action" value="draft">Сохранить черновик</x-button>
                @endif
                <x-button size="lg" variant="ghost" :href="route('shelter.pets.index')">Отмена</x-button>
            </div>
            @if (! $editing && ! $approved)
                <p class="-mt-3 text-sm text-secondary">Пока приют на проверке, анкету можно сохранить только как черновик.</p>
            @endif
        </form>
    </div>
</x-layouts.cabinet>
