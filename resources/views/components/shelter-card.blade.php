@use('App\Support\Plural')
@props([
    'shelter',
    'href' => null,
    'petsCount' => null,
    'campaignsCount' => null,
    'walks' => null,
    'district' => null,
    'headingLevel' => 3,
])
@php
    // Карточка приюта (UI-кит, артборд 04): название, город, статус проверки, цифры.
    $href ??= \Illuminate\Support\Facades\Route::has('shelters.show') ? route('shelters.show', $shelter) : '#';
    $logo = $shelter->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($shelter->logo_path) : null;
    $petsCount ??= $shelter->pets_count ?? null;
    $campaignsCount ??= $shelter->campaigns_count ?? null;
@endphp
<article {{ $attributes->class('relative flex flex-col gap-4 rounded-lg border border-subtle bg-raised p-5 transition-[box-shadow,border-color] duration-200 hover:border-ink-300 hover:shadow-md focus-within:border-ink-300') }}>
    <div class="flex items-start gap-4">
        <span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-pill bg-meadow-100 text-meadow-700">
            @if ($logo)
                <img src="{{ $logo }}" alt="" class="size-full object-cover">
            @else
                <x-icon name="home" size="lg" />
            @endif
        </span>
        <div class="min-w-0">
            <h{{ $headingLevel }} class="font-display text-lg leading-snug font-semibold tracking-display">
                <a href="{{ $href }}" class="after:absolute after:inset-0 after:rounded-lg focus-visible:outline-none focus-visible:after:outline-3 focus-visible:after:outline-offset-2 focus-visible:after:outline-focus">{{ $shelter->name }}</a>
            </h{{ $headingLevel }}>
            <p class="mt-0.5 text-sm text-secondary">{{ $shelter->city }}@if ($district), {{ $district }}@endif</p>
        </div>
    </div>
    <div><x-badge :status="$shelter->status" :icon="match ($shelter->status) { \App\Enums\ShelterStatus::Approved => 'shield-check', \App\Enums\ShelterStatus::Pending => 'clock', default => null }" /></div>
    @if (! is_null($petsCount) || ! is_null($campaignsCount) || $walks)
        <ul class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-secondary">
            @if (! is_null($petsCount))
                <li class="flex items-center gap-1.5"><x-icon name="paw" size="sm" />{{ Plural::format((int) $petsCount, ['питомец', 'питомца', 'питомцев']) }}</li>
            @endif
            @if (! is_null($campaignsCount))
                <li class="flex items-center gap-1.5"><x-icon name="tenge" size="sm" />{{ Plural::format((int) $campaignsCount, ['сбор', 'сбора', 'сборов']) }}</li>
            @endif
            @if ($walks)
                <li class="flex items-center gap-1.5"><x-icon name="leash" size="sm" />Выгул: {{ $walks }}</li>
            @endif
        </ul>
    @endif
</article>
