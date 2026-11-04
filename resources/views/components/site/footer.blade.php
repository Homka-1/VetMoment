@use('App\Support\Navigation')
{{-- Подвал (макет главной, этап 3): тёмная поверхность inverse, логотип для тёмного фона, три колонки ссылок. --}}
<footer class="bg-inverse text-ink-200">
    <div class="page-container grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr] lg:py-16">
        <div class="max-w-xs">
            <x-logo variant="inverse" />
            <p class="mt-4 text-sm">Помогаем приютам Казахстана найти хозяев, деньги на лечение и корм и волонтёров для прогулок.</p>
        </div>

        <nav aria-labelledby="footer-help">
            <h2 id="footer-help" class="text-sm font-semibold text-white">Помочь</h2>
            <ul class="mt-4 grid gap-2.5 text-sm">
                @foreach (array_slice(Navigation::main(), 0, 3) as $item)
                    <li><a href="{{ $item['href'] }}" class="hover:text-white hover:underline">{{ $item['label'] }}</a></li>
                @endforeach
                <li><a href="{{ Navigation::helpUrl() }}" class="hover:text-white hover:underline">Как помочь</a></li>
            </ul>
        </nav>

        <nav aria-labelledby="footer-shelters">
            <h2 id="footer-shelters" class="text-sm font-semibold text-white">Приютам</h2>
            <ul class="mt-4 grid gap-2.5 text-sm">
                <li><a href="{{ route('cabinet.shelter-request') }}" class="hover:text-white hover:underline">Стать приютом-партнёром</a></li>
                <li><a href="{{ route('shelter.dashboard') }}" class="hover:text-white hover:underline">Кабинет приюта</a></li>
                <li><a href="{{ Navigation::url('shelters.index', '/shelters') }}" class="hover:text-white hover:underline">Все приюты</a></li>
            </ul>
        </nav>

        <div>
            <h2 class="text-sm font-semibold text-white">Связаться</h2>
            <ul class="mt-4 grid gap-2.5 text-sm">
                <li><a href="mailto:hello@vetmoment.kz" class="hover:text-white hover:underline">hello@vetmoment.kz</a></li>
                <li><a href="tel:+77273001240" class="hover:text-white hover:underline">+7 727 300 12 40</a></li>
                <li>Алматы, ул. Сатпаева, 30</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-ink-800">
        <div class="page-container flex flex-col gap-3 py-5 text-xs sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} VetMoment</p>
            <a href="{{ Navigation::url('privacy', '/privacy') }}" class="underline underline-offset-3 hover:text-white">Политика обработки персональных данных</a>
        </div>
    </div>
</footer>
