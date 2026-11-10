@use('App\Support\Phone')
{{-- Волонтёр в записи: имя, телефон, пометка о частых неявках (W10). $booking, $noShows[user_id] => bool --}}
<p class="font-semibold">{{ $booking->user->name }}</p>
@if ($booking->user->phone)
    <a href="tel:{{ $booking->user->phone }}" class="text-xs text-secondary hover:underline">{{ Phone::format($booking->user->phone) }}</a>
@endif
@if ($noShows[$booking->user_id] ?? false)
    <p class="mt-1"><x-badge tone="warning" icon="alert-triangle">Часто не приходит</x-badge></p>
@endif
