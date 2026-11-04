@use('App\Support\Phone')
<x-layouts.cabinet :title="$shelter ? 'Исправить заявку приюта' : 'Стать приютом-партнёром'">
    <div class="grid max-w-narrow gap-6">
        @if ($shelter?->moderation_comment)
            <x-alert tone="danger" title="Причина отказа">{{ $shelter->moderation_comment }}</x-alert>
        @else
            <p class="type-lead text-secondary">После проверки приют сможет публиковать анкеты питомцев, открывать сборы и принимать волонтёров на выгул. Проверка занимает 1–2 рабочих дня.</p>
        @endif

        <x-form-errors />

        <x-card padding="lg">
            <form method="POST" action="{{ route('cabinet.shelter-request.store') }}" enctype="multipart/form-data" class="grid gap-5" novalidate>
                @csrf
                <x-input name="name" label="Название приюта" :value="$shelter?->name" required maxlength="150" placeholder="Например, Дом лап" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="city" label="Город" :value="$shelter?->city" required maxlength="100" placeholder="Алматы" />
                    <x-input name="address" label="Адрес" :value="$shelter?->address" required maxlength="255" placeholder="ул. Сатпаева, 30" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="phone" type="tel" label="Телефон приюта" :value="$shelter ? Phone::format($shelter->phone) : null" required
                             inputmode="tel" placeholder="+7 727 123 45 67" hint="Мобильный или городской" />
                    <x-input name="email" type="email" label="Эл. почта приюта" :value="$shelter?->email" required placeholder="info@domlap.kz" />
                </div>
                <x-textarea name="description" label="Описание" :value="$shelter?->description" required minlength="50" maxlength="5000" rows="6"
                            hint="Кто вы, сколько животных, чем можно помочь. От 50 символов." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="website" type="url" label="Сайт или соцсети" :value="$shelter?->website" maxlength="255" hint="Необязательно" placeholder="https://" />
                    <x-input name="bin_iin" label="БИН / ИИН" :value="$shelter?->bin_iin" inputmode="numeric" maxlength="14" hint="Необязательно, 12 цифр" />
                </div>
                <x-form.field label="Логотип" for="f-logo" hint="JPG, PNG или WebP до 2 МБ, необязательно" :error="$errors->first('logo')">
                    <input type="file" name="logo" id="f-logo" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-secondary file:mr-4 file:h-control file:cursor-pointer file:rounded-sm file:border-control file:border-ink-900 file:bg-white file:px-4 file:font-semibold file:text-primary hover:file:bg-ink-50">
                </x-form.field>
                <x-checkbox name="agree">Согласен(на) с правилами площадки и обработкой персональных данных</x-checkbox>
                <div class="pt-2">
                    <x-button size="lg">Отправить на модерацию</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.cabinet>
