@use('App\Support\Phone')
{{-- Профиль приюта: то, что видят посетители на странице приюта. Меняет владелец. --}}
<x-layouts.cabinet title="Профиль приюта">
    <x-slot:actions>
        @if ($shelter->isApproved())
            <x-button variant="secondary" icon="home" :href="route('shelters.show', $shelter)">Страница приюта на сайте</x-button>
        @endif
    </x-slot:actions>

    <div class="grid max-w-narrow gap-6">
        <x-shelter-moderation :shelter="$shelter" />
        <x-form-errors />

        <x-card padding="lg">
            <form method="POST" action="{{ route('shelter.profile.update') }}" enctype="multipart/form-data" class="grid gap-5" novalidate>
                @csrf
                @method('PATCH')
                <x-input name="name" label="Название приюта" :value="$shelter->name" required maxlength="150" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="city" label="Город" :value="$shelter->city" required maxlength="100" />
                    <x-input name="address" label="Адрес" :value="$shelter->address" required maxlength="255" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="phone" type="tel" label="Телефон приюта" :value="Phone::format($shelter->phone)" required inputmode="tel" hint="Мобильный или городской" />
                    <x-input name="email" type="email" label="Эл. почта приюта" :value="$shelter->email" required />
                </div>
                <x-input name="website" type="url" label="Сайт или соцсети" :value="$shelter->website" maxlength="255" hint="Необязательно" placeholder="https://" />
                <x-textarea name="description" label="О приюте" :value="$shelter->description" required minlength="50" maxlength="5000" rows="7"
                            hint="Кто вы, сколько животных, чем можно помочь. От 50 символов." />
                <x-textarea name="walk_rules" label="Правила выгула" :value="$shelter->walk_rules" maxlength="2000" rows="5"
                            hint="Каждое правило — с новой строки. Волонтёр увидит их при записи на выгул." />

                <x-form.field label="Логотип" for="f-logo" hint="JPG, PNG или WebP до 2 МБ" :error="$errors->first('logo')">
                    @if ($shelter->logo_path)
                        <img src="{{ Storage::disk('public')->url($shelter->logo_path) }}" alt="Текущий логотип приюта «{{ $shelter->name }}»" class="mb-3 size-20 rounded-pill object-cover">
                    @endif
                    <input type="file" name="logo" id="f-logo" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-secondary file:mr-4 file:h-control file:cursor-pointer file:rounded-sm file:border-control file:border-ink-900 file:bg-white file:px-4 file:font-semibold file:text-primary hover:file:bg-ink-50">
                </x-form.field>
                @if ($shelter->logo_path)
                    <x-checkbox name="remove_logo" value="1">Удалить текущий логотип</x-checkbox>
                @endif

                <p class="text-sm text-secondary">Статус проверки и БИН/ИИН здесь не меняются — их видит только модератор.</p>
                <div><x-button size="lg">Сохранить</x-button></div>
            </form>
        </x-card>
    </div>
</x-layouts.cabinet>
