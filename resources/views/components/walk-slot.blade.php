@use('App\Enums\WalkSlotState')
@use('App\Support\LocalTime')
@use('App\Support\Plural')
@props([
    'model' => null,
    'start' => null,
    'end' => null,
    'state' => null,
    'spots' => null,
    'name' => 'slot_id',
    'value' => null,
    'checked' => false,
    'id' => null,
])
@php
    // Слот выгула (UI-кит, артборд 03). Это радиокнопка: выбранный — тёмная плашка «Вы выбрали».
    // Компонент <x-walk-slot> (имя x-slot занято Blade). Принимает модель WalkSlot (:model) или start/end/state/spots по отдельности.
    if ($model instanceof \App\Models\WalkSlot) {
        $start ??= $model->starts_at;
        $end ??= $model->ends_at;
        $state ??= $model->state();
        $spots ??= $model->spotsLeft();
        $value ??= $model->id;
    }
    $state = $state instanceof WalkSlotState ? $state : WalkSlotState::from($state ?? 'available');
    $bookable = $state->isBookable();
    $range = $start && $end ? LocalTime::timeRange($start, $end) : '';

    $note = match ($state) {
        WalkSlotState::Available, WalkSlotState::FewLeft => 'Осталось '.Plural::format((int) $spots, ['место', 'места', 'мест']),
        WalkSlotState::Full => 'Мест нет',
        WalkSlotState::Closed => 'Запись закрыта',
        WalkSlotState::Past => 'Уже прошёл',
        WalkSlotState::Cancelled => 'Отменён',
    };
    $noteIcon = match ($state) {
        WalkSlotState::Available => 'leash',
        WalkSlotState::FewLeft => 'alert-triangle',
        WalkSlotState::Full, WalkSlotState::Cancelled => 'x',
        WalkSlotState::Past, WalkSlotState::Closed => 'clock',
    };
    $id ??= 'slot-'.($value ?? uniqid());
@endphp
<label for="{{ $id }}" {{ $attributes->class([
    'group relative grid min-w-[148px] gap-1 rounded-sm border-control px-4 py-3 transition-colors duration-120',
    'has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus',
    'cursor-pointer has-checked:border-ink-900 has-checked:bg-ink-900 has-checked:text-white' => $bookable,
    'border-meadow-600 bg-white hover:bg-meadow-100' => $state === WalkSlotState::Available,
    'border-tag-600 bg-white hover:bg-tag-100' => $state === WalkSlotState::FewLeft,
    'cursor-not-allowed border-ink-200 bg-ink-100 text-ink-500' => in_array($state, [WalkSlotState::Full, WalkSlotState::Closed, WalkSlotState::Cancelled], true),
    'cursor-not-allowed border-dashed border-ink-200 bg-white text-ink-400' => $state === WalkSlotState::Past,
]) }}>
    <input type="radio" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" class="peer sr-only"
           @checked($checked) @disabled(! $bookable)>
    <span @class([
        'font-display text-base font-semibold tracking-display tabular-nums',
        'line-through' => $state === WalkSlotState::Past,
    ])>
        <time datetime="{{ $start ? LocalTime::iso($start) : '' }}">{{ $range }}</time>
    </span>
    {{-- Подпись меняется на «Вы выбрали» при выборе --}}
    <span @class([
        'flex items-center gap-1.5 text-xs font-semibold group-has-checked:hidden',
        'text-meadow-700' => $state === WalkSlotState::Available,
        'text-warning-fg' => $state === WalkSlotState::FewLeft,
    ])>
        <x-icon :name="$noteIcon" size="sm" class="size-3.5" />{{ $note }}
    </span>
    @if ($bookable)
        <span class="hidden items-center gap-1.5 text-xs font-semibold text-tag-500 group-has-checked:flex">
            <x-icon name="check" size="sm" class="size-3.5" />Вы выбрали
        </span>
    @endif
</label>
