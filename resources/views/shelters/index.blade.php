{{-- Приюты-партнёры: проверенные приюты с цифрами; фильтр по городу. --}}
<x-layouts.app title="Приюты" description="Проверенные приюты Казахстана: питомцы, сборы и выгул собак. Выберите приют рядом с вами.">
    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Приюты' => null]" />

        <h1 class="type-h1 mt-6">Приюты</h1>
        <p class="type-lead mt-4 max-w-2xl text-secondary">
            Каждый приют проходит проверку перед публикацией: мы сверяем документы и звоним заявителю.
        </p>

        @if ($cities->count() > 1)
            <form method="GET" action="{{ route('shelters.index') }}" data-autosubmit class="mt-8 max-w-xs">
                <x-select name="city" label="Город" :options="$cities->mapWithKeys(fn ($c) => [$c => $c])->all()" :value="$city" placeholder="Все города" />
                <x-button class="mt-3" variant="secondary" size="sm" data-autosubmit-fallback>Показать</x-button>
            </form>
        @endif

        @if ($shelters->isEmpty())
            <x-empty-state class="mt-8" title="Приютов пока нет" illustration="dog-caramel">
                Первые приюты появятся после проверки. Если вы представляете приют, подайте заявку — это бесплатно.
                <x-slot:action><x-button icon="home" :href="auth()->check() ? route('cabinet.shelter-request') : route('register')">Стать приютом-партнёром</x-button></x-slot:action>
            </x-empty-state>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                @foreach ($shelters as $shelter)
                    <x-shelter-card :shelter="$shelter" heading-level="2" />
                @endforeach
            </div>
            <x-pagination class="mt-8" :paginator="$shelters" />
        @endif
    </div>
</x-layouts.app>
