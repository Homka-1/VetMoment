@use('App\Support\LocalTime')
@props([
    'notification',
])
{{-- Строка уведомления (/notifications). Непрочитанное — на белом, с бирочной точкой и жирным заголовком;
     клик ведёт через notifications.open: отметка «прочитано» и переход к записи, заявке или сбору.
     data = ['title', 'body', 'url', 'icon', 'tone'] — см. App\Notifications\AppNotification::toArray(). --}}
@php
    $data = $notification->data;
    $unread = $notification->read_at === null;
    $discs = [
        'neutral' => 'bg-ink-100 text-ink-700',
        'info' => 'bg-sky-100 text-sky-700',
        'success' => 'bg-success-bg text-success-fg',
        'warning' => 'bg-warning-bg text-warning-fg',
        'danger' => 'bg-danger-bg text-danger-fg',
        'tag' => 'bg-tag-100 text-tag-700',
        'meadow' => 'bg-meadow-100 text-meadow-700',
    ];
    $created = $notification->created_at;
    $when = $created->greaterThan(now()->subDay()) ? $created->diffForHumans() : LocalTime::time($created);
@endphp
<li {{ $attributes }}>
    <a href="{{ route('notifications.open', $notification->id) }}"
       @class([
           'group relative grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 rounded-md px-4 py-4 transition-colors duration-120 sm:grid-cols-[auto_1fr_auto] sm:px-5',
           'bg-white shadow-sm hover:bg-ink-50' => $unread,
           'hover:bg-white' => ! $unread,
       ])>
        <span @class(['relative row-span-2 grid size-10 place-items-center rounded-pill sm:row-span-1', $discs[$data['tone'] ?? 'info'] ?? $discs['info']])>
            <x-icon :name="$data['icon'] ?? 'bell'" />
            @if ($unread)
                <span class="absolute -top-0.5 -right-0.5 size-3 rounded-pill bg-tag-500 ring-2 ring-white" aria-hidden="true"></span>
            @endif
        </span>
        <span class="min-w-0">
            @if ($unread)<span class="sr-only">Новое: </span>@endif
            <span @class(['block text-primary group-hover:underline', 'font-semibold' => $unread, 'font-medium' => ! $unread])>{{ $data['title'] ?? 'Уведомление' }}</span>
            <span class="mt-1 block max-w-prose text-sm text-secondary">{{ $data['body'] ?? '' }}</span>
        </span>
        <time datetime="{{ LocalTime::iso($created) }}" title="{{ LocalTime::dateTime($created) }}"
              class="col-start-2 text-xs whitespace-nowrap text-muted sm:col-start-3 sm:row-start-1 sm:pt-0.5">{{ $when }}</time>
    </a>
</li>
