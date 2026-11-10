@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
{{-- Обзор кабинета волонтёра (макет 09-overview): ближайший выгул, заявки, пожертвования, избранное. --}}
<x-layouts.cabinet title="Обзор" :heading="'Здравствуйте, '.(preg_split('/\s+/u', trim($user->name))[0] ?? $user->name)">
    <div class="grid grid-cols-1 gap-8">
        @if ($nextWalk)
            @php $slot = $nextWalk->slot; @endphp
            <section aria-labelledby="next-walk" class="flex flex-col gap-5 rounded-lg bg-path-walk p-5 sm:flex-row sm:items-center sm:p-6">
                <x-pet-thumb :pet="$nextWalk->pet" size="lg" class="hidden sm:block" />
                <div class="min-w-0 flex-1">
                    <p id="next-walk" class="text-sm font-semibold text-meadow-700">Ближайший выгул</p>
                    <p class="type-h3 mt-1">{{ Str::ucfirst(LocalTime::dayLong($slot->starts_at)) }}, {{ LocalTime::time($slot->starts_at) }}</p>
                    <p class="mt-2 text-secondary">{{ $nextWalk->pet?->name ?? 'Собаку подберёт приют' }}, приют «{{ $slot->shelter->name }}», {{ $slot->meeting_point ?: $slot->shelter->address }}</p>
                    <p class="mt-3"><x-badge :status="$nextWalk->status" /></p>
                </div>
                <div class="flex flex-col gap-2 sm:items-center">
                    <x-button variant="secondary" icon="map-pin" :href="route('walks.bookings.show', $nextWalk)">Как добраться</x-button>
                    @if ($walksCount > 1)
                        <a href="{{ route('cabinet.walks') }}" class="link text-sm">Все записи: {{ $walksCount }}</a>
                    @endif
                </div>
            </section>
        @else
            <x-empty-state title="Прогулок пока нет" illustration="dog-caramel" tone="meadow">
                Выберите приют и свободное время — прогулка занимает около часа.
                <x-slot:action><x-button icon="leash" :href="route('walks.index')">Выбрать время</x-button></x-slot:action>
            </x-empty-state>
        @endif

        <div class="grid grid-cols-1 gap-8 xl:grid-cols-2">
            <section aria-labelledby="my-applications" class="min-w-0">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="my-applications" class="type-h4">Мои заявки</h2>
                    <a href="{{ route('cabinet.applications') }}" class="link inline-flex shrink-0 items-center gap-1 text-sm whitespace-nowrap">Все<x-icon name="chevron-right" size="sm" /></a>
                </div>
                <x-card class="mt-4 h-[calc(100%-2.75rem)]">
                    @if ($application)
                        <div class="flex items-center gap-3">
                            <x-pet-thumb :pet="$application->pet" size="md" />
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $application->pet->name }}</p>
                                <p class="text-sm text-secondary">{{ $application->pet->shelter->name }}, {{ $application->pet->shelter->city }}</p>
                            </div>
                        </div>
                        <p class="mt-4"><x-badge :status="$application->status" /></p>
                        <p class="mt-3 text-secondary">
                            {{ $application->type->label() }}.
                            @if ($application->shelter_comment)
                                {{ $application->shelter_comment }}
                            @elseif ($application->status->isActive())
                                Приют свяжется с вами по телефону, чтобы договориться о знакомстве.
                            @endif
                        </p>
                    @else
                        <p class="text-secondary">Заявок пока нет. Нашли питомца — нажмите «Хочу забрать» в его анкете, приют перезвонит.</p>
                        <x-button class="mt-4" variant="secondary" size="sm" icon="paw" :href="route('pets.index')">Смотреть питомцев</x-button>
                    @endif
                </x-card>
            </section>

            <section aria-labelledby="my-donations" class="min-w-0">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="my-donations" class="type-h4">Мои пожертвования</h2>
                    <a href="{{ route('cabinet.donations') }}" class="link inline-flex shrink-0 items-center gap-1 text-sm whitespace-nowrap">Все<x-icon name="chevron-right" size="sm" /></a>
                </div>
                <x-card class="mt-4 h-[calc(100%-2.75rem)]">
                    @if ($donationsCount > 0)
                        <p class="type-number">{{ Money::format($donationsTotal) }}</p>
                        <p class="mt-1 text-sm text-secondary">за {{ now()->year }} год, {{ Plural::format($donationsCount, ['пожертвование', 'пожертвования', 'пожертвований']) }}</p>
                        <ul class="mt-4 divide-y divide-subtle border-t border-subtle text-sm">
                            @foreach ($recentDonations as $donation)
                                <li class="flex items-center justify-between gap-4 py-3">
                                    <span class="min-w-0 truncate">{{ $donation->campaign?->title ?? 'Общий фонд, '.$donation->shelter->name }}</span>
                                    <span class="shrink-0 font-semibold tabular-nums">{{ Money::format($donation->amount) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-secondary">В этом году вы ещё никому не помогли деньгами. Любая сумма от 200 ₸ идёт прямо приюту, а по сбору приют публикует отчёт.</p>
                        <x-button class="mt-4" variant="donate" size="sm" icon="heart" :href="route('campaigns.index')">Выбрать сбор</x-button>
                    @endif
                </x-card>
            </section>
        </div>

        <section aria-labelledby="my-favorites">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="my-favorites" class="type-h4">Избранное</h2>
                @if ($favoritesCount > 0)
                    <a href="{{ route('cabinet.favorites') }}" class="link inline-flex shrink-0 items-center gap-1 text-sm whitespace-nowrap">Все {{ $favoritesCount }}<x-icon name="chevron-right" size="sm" /></a>
                @endif
            </div>
            @if ($favorites->isEmpty())
                <p class="mt-4 text-secondary">Нажмите сердечко на карточке питомца — он появится здесь, и вы не потеряете его из виду.</p>
            @else
                <div class="mt-4 grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
                    @foreach ($favorites as $pet)
                        <x-pet-card :pet="$pet" class="w-full" responsive heading-level="3" :favorite-url="route('pets.favorite', $pet)" favorited />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.cabinet>
