@props([
    'steps' => [],
    'current' => 1,
])
{{-- Шаги сценария (макет 06): пройденный — зелёный кружок с галочкой, текущий — тёмный с номером,
     следующий — контурный. На телефоне подпись только у текущего шага. --}}
<ol {{ $attributes->class('flex items-center gap-3') }} aria-label="Шаги записи">
    @foreach ($steps as $i => $label)
        @php
            $n = $i + 1;
            $done = $n < $current;
            $active = $n === $current;
        @endphp
        <li class="flex items-center gap-3" @if ($active) aria-current="step" @endif>
            <span @class([
                'grid size-8 shrink-0 place-items-center rounded-pill text-sm font-bold tabular-nums',
                'bg-meadow-600 text-white' => $done,
                'bg-ink-900 text-white' => $active,
                'border-control border-ink-300 text-secondary' => ! $done && ! $active,
            ])>
                @if ($done)
                    <x-icon name="check" size="sm" /><span class="sr-only">Шаг {{ $n }} пройден:</span>
                @else
                    {{ $n }}
                @endif
            </span>
            <span @class([
                'text-sm font-semibold sm:text-base',
                'max-sm:sr-only' => ! $active,
                'text-primary' => $done || $active,
                'font-medium text-secondary' => ! $done && ! $active,
            ])>{{ $label }}</span>
            @unless ($loop->last)
                <span class="h-px w-6 bg-ink-300 sm:w-10" aria-hidden="true"></span>
            @endunless
        </li>
    @endforeach
</ol>
