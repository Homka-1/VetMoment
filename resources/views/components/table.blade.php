@props(['caption' => null])
{{-- Таблица кабинетов (макеты этапа 3): шапка 44, строки 68–80, граница subtle, прокрутка по горизонтали на узких экранах.
     Слот head — строки <tr> шапки, основной слот — строки <tr> тела. Ячейки: x-table.th / обычные <td class="…">. --}}
<div {{ $attributes->class('relative overflow-x-auto rounded-lg border border-subtle bg-raised') }}>
    <table class="w-full border-collapse text-left text-sm">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        <thead class="bg-canvas text-xs font-semibold text-muted">
            {{ $head }}
        </thead>
        <tbody class="divide-y divide-subtle [&_td]:px-4 [&_td]:py-4 [&_td]:align-middle [&_td]:first:pl-6 [&_td]:last:pr-6">
            {{ $slot }}
        </tbody>
    </table>
</div>
