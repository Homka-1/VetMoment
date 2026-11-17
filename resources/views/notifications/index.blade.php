@use('App\Http\Controllers\NotificationController')
@use('App\Support\LocalTime')
{{-- Уведомления (карта сайта: /notifications — список, «прочитать все»). Группы по дням: сегодня, вчера, дата. --}}
@php
    $groups = $notifications->getCollection()->groupBy(function ($n) {
        $day = LocalTime::of($n->created_at)->startOfDay();
        $today = LocalTime::of(now())->startOfDay();

        return match (true) {
            $day->equalTo($today) => 'Сегодня',
            $day->equalTo($today->copy()->subDay()) => 'Вчера',
            default => LocalTime::date($n->created_at),
        };
    });
@endphp
<x-layouts.cabinet title="Уведомления">
    @if ($counts['unread'] > 0)
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <x-button variant="secondary" icon="check">Прочитать все</x-button>
            </form>
        </x-slot:actions>
    @endif

    <div class="grid grid-cols-1 gap-6">
        <x-tabs label="Уведомления" :items="collect(NotificationController::TABS)->map(fn ($label, $key) => [
            'label' => $label,
            'href' => route('notifications.index', $key === 'all' ? [] : ['tab' => $key]),
            'active' => $tab === $key,
            'count' => $counts[$key],
        ])->values()->all()" />

        @if ($notifications->isEmpty())
            <x-empty-state :title="$tab === 'unread' ? 'Новых уведомлений нет' : 'Уведомлений пока нет'" illustration="cat-smoke" tone="sky">
                Мы напишем, когда приют ответит на заявку или запись на выгул.
                @if ($tab === 'unread' && $counts['all'] > 0)
                    <x-slot:action><x-button variant="secondary" :href="route('notifications.index')">Все уведомления</x-button></x-slot:action>
                @endif
            </x-empty-state>
        @else
            @foreach ($groups as $label => $items)
                <section aria-labelledby="notifications-{{ $loop->index }}">
                    <h2 id="notifications-{{ $loop->index }}" class="mb-2 text-sm font-semibold text-muted">{{ $label }}</h2>
                    <ol class="grid gap-1">
                        @foreach ($items as $notification)
                            <x-notification-item :notification="$notification" />
                        @endforeach
                    </ol>
                </section>
            @endforeach
            <x-pagination :paginator="$notifications" />
        @endif
    </div>
</x-layouts.cabinet>
