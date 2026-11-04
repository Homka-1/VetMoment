@use('App\Enums\ApplicationType')
{{-- Заявка «Забрать домой» / «Взять под опеку» (макет 03-apply). --}}
@php
    $cover = $pet->cover?->thumbUrl();
    $selected = old('type', $type->value);
    $options = [
        ApplicationType::Adoption->value => ['title' => 'Забрать домой', 'text' => $pet->name.' переедет к вам насовсем'],
    ];
    if ($pet->guardianship_available) {
        $options[ApplicationType::Guardianship->value] = ['title' => 'Взять под опеку', 'text' => $pet->name.' живёт в приюте, вы помогаете'];
    }
@endphp
<x-layouts.app :title="'Заявка на питомца '.$pet->name">
    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Питомцы' => route('pets.index'), $pet->name => route('pets.show', $pet), 'Заявка' => null]" />

        <div class="mx-auto mt-8 max-w-narrow lg:mt-14">
            <div class="flex items-center gap-4 md:gap-5">
                <span class="relative size-16 shrink-0 overflow-hidden rounded-md bg-sky-100 md:size-[72px]">
                    @if ($cover)
                        <img src="{{ $cover }}" alt="" class="size-full object-cover">
                    @else
                        <img src="{{ asset('images/illustrations/'.($pet->species === \App\Enums\Species::Cat ? 'cat-smoke' : 'dog-caramel').'.svg') }}" alt=""
                             class="absolute inset-x-0 bottom-0 mx-auto h-[90%] w-auto">
                    @endif
                </span>
                <div class="min-w-0">
                    <h1 class="type-h2 break-words">Заявка на питомца {{ $pet->name }}</h1>
                    <p class="mt-1 text-secondary">{{ $pet->shelter->name }}, {{ $pet->shelter->city }}. Приют ответит в течение двух дней.</p>
                </div>
            </div>

            @if ($reserved)
                <x-alert tone="warning" title="{{ $pet->name }} уже забронирован" class="mt-6">
                    Заявка встанет в резерв: приют напишет, если планы изменятся.
                </x-alert>
            @endif

            <x-form-errors class="mt-6" />

            <form method="POST" action="{{ route('pets.apply.store', $pet) }}" class="mt-8 grid gap-6" novalidate>
                @csrf

                <fieldset>
                    <legend class="type-label mb-3">Что вы хотите</legend>
                    <div @class(['grid gap-3', 'md:grid-cols-2' => count($options) > 1])>
                        @foreach ($options as $value => $option)
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border-control border-ink-300 bg-white p-5 transition-colors hover:border-ink-900
                                          has-checked:border-ink-900 has-checked:bg-sky-100 has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus">
                                <input type="radio" name="type" value="{{ $value }}" @checked($selected === $value)
                                       class="mt-0.5 size-5 shrink-0 cursor-pointer appearance-none rounded-pill border-control border-strong bg-white transition-[border-width,border-color] checked:border-[6px] checked:border-ink-900 focus-visible:outline-none">
                                <span class="grid gap-1">
                                    <span class="font-semibold">{{ $option['title'] }}</span>
                                    <span class="text-sm text-secondary">{{ $option['text'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="grid gap-6 md:grid-cols-2">
                    <x-input name="contact_name" label="Имя" :value="$user->name" required autocomplete="name" maxlength="100" />
                    <x-input name="contact_phone" label="Телефон" type="tel" :value="$phone" required autocomplete="tel" inputmode="tel" />
                    <x-input name="city" label="Город" :value="$pet->shelter->city" required autocomplete="address-level2" maxlength="100" />
                    <x-select name="living_conditions" label="Жильё" :options="array_combine($housing, $housing)" placeholder="Выберите вариант"
                              hint="Для заявки «Забрать домой»" />
                </div>

                <x-textarea name="experience" label="Расскажите о себе" rows="4" maxlength="2000"
                            hint="Опыт с животными, кто живёт с вами, сколько времени на прогулки" />

                <x-checkbox name="agree" hint="Приют увидит имя, телефон и ответы из анкеты.">
                    Согласен(на) на обработку персональных данных
                </x-checkbox>

                <div class="flex flex-wrap items-center gap-4">
                    <x-button size="lg">Отправить заявку</x-button>
                    <x-button variant="ghost" size="lg" :href="route('pets.show', $pet)">Отмена</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
