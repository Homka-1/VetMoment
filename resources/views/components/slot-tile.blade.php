@use('App\Enums\WalkSlotState')
@use('App\Support\LocalTime')
@use('App\Support\Plural')
@props(['model'])
{{-- Слот выгула как ссылка (главная, страница приюта): время и сколько мест. Выбор слота — на /walks, здесь только переход. --}}
@php
    $state = $model->state();
    $bookable = $state->isBookable();
    $note = match ($state) {
        WalkSlotState::Available, WalkSlotState::FewLeft => 'Осталось '.Plural::format($model->spotsLeft(), ['место', 'места', 'мест']),
        WalkSlotState::Full => 'Мест нет',
        default => 'Запись закрыта',
    };
@endphp
<a href="{{ $bookable ? route('walks.slots.show', $model) : route('walks.index', ['shelter' => $model->shelter->slug]) }}"
   {{ $attributes->class([
       'block rounded-md border-control px-4 py-3 transition-colors duration-120',
       'border-meadow-500 bg-white hover:bg-meadow-100' => $state === WalkSlotState::Available,
       'border-tag-500 bg-white hover:bg-tag-100' => $state === WalkSlotState::FewLeft,
       'border-transparent bg-ink-100 text-muted' => ! $bookable,
   ]) }}>
    <span class="block font-semibold tabular-nums">{{ LocalTime::timeRange($model->starts_at, $model->ends_at) }}</span>
    <span @class(['mt-1 flex items-center gap-1.5 text-xs font-semibold', 'text-meadow-700' => $state === WalkSlotState::Available, 'text-tag-700' => $state === WalkSlotState::FewLeft])>
        <x-icon :name="$bookable ? ($state === WalkSlotState::FewLeft ? 'alert-triangle' : 'leash') : 'x'" size="sm" />{{ $note }}
    </span>
</a>
