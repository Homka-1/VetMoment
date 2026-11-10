@use('App\Enums\WalkSlotState')
@use('App\Support\LocalTime')
@use('App\Support\Plural')
{{-- Слоты выгула (макет 10-slots): неделя по дням, плашка слота — «записано N/M» и состояние,
     ниже — форма разового слота; шаблон недели — отдельная страница. --}}
@php
    $weekUrl = fn (string $key) => route('shelter.slots.index', ['week' => $key]);
    $navClass = 'grid size-11 place-items-center rounded-sm border-control border-ink-900 bg-white transition-colors duration-120 hover:bg-ink-50';
@endphp
<x-layouts.cabinet title="Слоты выгула">
    <x-slot:actions>
        <x-button variant="secondary" icon="calendar" :href="route('shelter.slot-templates.index')">Шаблон недели</x-button>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-8">
        <x-shelter-moderation :shelter="$shelter" />

        <div class="flex items-center gap-3">
            <a href="{{ $weekUrl($week->previousKey()) }}" class="{{ $navClass }}" aria-label="Предыдущая неделя"><x-icon name="chevron-left" /></a>
            <h2 class="type-h4 min-w-36 text-center font-semibold"><span class="sr-only">Неделя: </span>{{ $week->label() }}</h2>
            <a href="{{ $weekUrl($week->nextKey()) }}" class="{{ $navClass }}" aria-label="Следующая неделя"><x-icon name="chevron-right" /></a>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
            @foreach ($days as $day)
                <section aria-labelledby="sday-{{ $day['key'] }}" @class([
                    'flex min-w-0 flex-col gap-2 rounded-md p-2 lg:min-h-80',
                    'border bg-white',
                    'border-ink-900 ring-1 ring-ink-900' => $day['today'],
                    'border-subtle' => ! $day['today'],
                ])>
                    <h3 id="sday-{{ $day['key'] }}" class="px-1 pt-1 pb-1">
                        <span class="block text-xs font-medium text-secondary">{{ Str::ucfirst(LocalTime::weekdayShort($day['date'])) }}</span>
                        <span class="type-h4 font-semibold tabular-nums max-lg:hidden">{{ $day['date']->day }}</span>
                        <span class="type-h4 font-semibold tabular-nums lg:hidden">{{ LocalTime::date($day['date']) }}</span>
                    </h3>
                    @forelse ($day['slots'] as $slot)
                        @php
                            $state = $slot->state();
                        @endphp
                        <a href="{{ route('shelter.slots.show', $slot) }}" @class([
                            'grid gap-0.5 rounded-sm border-control bg-white px-3 py-2.5 text-sm transition-shadow duration-120 hover:shadow-md',
                            'border-meadow-600' => $state === WalkSlotState::Available,
                            'border-tag-600' => $state === WalkSlotState::FewLeft,
                            'border-ink-200 bg-ink-100!' => in_array($state, [WalkSlotState::Full, WalkSlotState::Closed], true),
                            'border-dashed border-ink-300 text-muted' => in_array($state, [WalkSlotState::Past, WalkSlotState::Cancelled], true),
                        ])>
                            <span @class(['font-display text-base font-semibold tabular-nums', 'line-through' => $state === WalkSlotState::Cancelled])>
                                {{ LocalTime::time($slot->starts_at) }}<span class="sr-only">–{{ LocalTime::time($slot->ends_at) }}</span>
                            </span>
                            <span class="text-secondary">записано {{ $slot->booked_count }}/{{ $slot->capacity }}</span>
                            @if (! $state->isBookable())
                                <span class="text-xs text-muted">{{ mb_strtolower($state->label()) }}</span>
                            @endif
                            @if ($slot->pending_count > 0 && $state !== WalkSlotState::Cancelled)
                                <span class="mt-1 inline-flex w-fit items-center gap-1 rounded-pill bg-warning-bg px-2 py-0.5 text-xs font-semibold text-warning-fg">
                                    <x-icon name="clock" size="sm" class="size-3" />{{ $slot->pending_count }} {{ Plural::choose($slot->pending_count, ['ждёт', 'ждут', 'ждут']) }}
                                </span>
                            @endif
                        </a>
                    @empty
                        <p class="grid flex-1 place-items-center py-4 text-sm text-muted">нет</p>
                    @endforelse
                </section>
            @endforeach
        </div>

        @if ($canCreate)
            <x-card padding="lg" as="section" aria-labelledby="new-slot-title">
                <h2 id="new-slot-title" class="type-h4 font-semibold">Новый разовый слот</h2>
                <form method="POST" action="{{ route('shelter.slots.store') }}" class="mt-5 grid gap-5" novalidate>
                    @csrf
                    @include('shelter.slots.partials.fields', [
                        'wide' => true,
                        'values' => [
                            'date' => $defaultDate->format('Y-m-d'),
                            'start_time' => '10:00',
                            'end_time' => '11:00',
                            'capacity' => 4,
                            'meeting_point' => $shelter->walkTemplates()->latest()->value('meeting_point'),
                            'pets' => [],
                        ],
                    ])
                    <div class="flex flex-wrap items-center gap-4">
                        <x-button icon="plus">Создать слот</x-button>
                        <p class="text-sm text-secondary">Волонтёры увидят слот сразу после создания.</p>
                    </div>
                </form>
            </x-card>
        @endif
    </div>
</x-layouts.cabinet>
