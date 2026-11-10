@use('App\Enums\PaymentStatus')
@use('App\Support\Money')
@use('Illuminate\Support\Facades\Route')
{{-- Результат платежа /donations/{uuid} (макеты 05-paid, 05-pending, 05-failed) + истёк срок и возврат.
     Ожидание обновляет страницу само, пока не вышел срок оплаты. Имя жертвователя видит только тот, кто платил. --}}
@php
    $campaign = $donation->campaign;
    $shelter = $donation->shelter;
    $status = $donation->status;
    $amount = Money::format($donation->amount);
    $backUrl = $campaign ? route('campaigns.show', $campaign) : route('shelters.donate', $shelter);
    $backLabel = $campaign ? 'Вернуться к сбору' : 'Вернуться к приюту';
    $retryUrl = $campaign
        ? route('campaigns.donate', [$campaign, 'retry' => $donation->uuid])
        : route('shelters.donate', [$shelter, 'retry' => $donation->uuid]);
    $shareUrl = $campaign ? route('campaigns.show', $campaign) : (Route::has('shelters.show') ? route('shelters.show', $shelter) : route('campaigns.index'));
    $target = $campaign ? '«'.$campaign->title.'»' : 'общий фонд приюта «'.$shelter->name.'»';
    $name = $owner && filled($donation->donor_name) ? $donation->donor_name : null;
    $waiting = $status === PaymentStatus::Pending
        && $donation->created_at->greaterThan(now()->subMinutes((int) config('payments.pending_ttl_minutes', 60)));

    $state = match ($status) {
        PaymentStatus::Succeeded => ['icon' => 'check-circle', 'circle' => 'bg-success-bg text-success-fg', 'title' => $name ? "Спасибо, {$name}!" : 'Спасибо!'],
        PaymentStatus::Pending => ['icon' => null, 'circle' => 'bg-tag-100 text-tag-700', 'title' => 'Ждём ответ банка'],
        PaymentStatus::Failed => ['icon' => 'alert-circle', 'circle' => 'bg-danger-bg text-danger-fg', 'title' => 'Платёж не прошёл'],
        PaymentStatus::Expired => ['icon' => 'clock', 'circle' => 'bg-ink-100 text-secondary', 'title' => 'Время на оплату вышло'],
        PaymentStatus::Refunded => ['icon' => 'info', 'circle' => 'bg-info-bg text-info-fg', 'title' => 'Платёж возвращён'],
    };
@endphp
<x-layouts.app :title="$state['title']" canvas>
    <x-slot:head>
        <meta name="robots" content="noindex">
        @if ($waiting)
            <meta http-equiv="refresh" content="5">
        @endif
    </x-slot:head>

    <div class="page-container py-10 lg:py-20">
        <section class="mx-auto grid max-w-[640px] justify-items-center gap-4 rounded-lg border border-subtle bg-raised px-6 py-10 text-center sm:px-10" aria-labelledby="result-title">
            <span @class(['grid size-18 place-items-center rounded-pill', $state['circle']])>
                @if ($state['icon'])
                    <x-icon :name="$state['icon']" size="lg" />
                @else
                    <span class="size-8 animate-spin rounded-pill border-3 border-tag-200 border-t-tag-700" aria-hidden="true"></span>
                @endif
            </span>
            <h1 id="result-title" class="type-h3" @if ($waiting) aria-live="polite" @endif>{{ $state['title'] }}</h1>

            <div class="grid max-w-[460px] gap-2 text-secondary">
                @switch($status)
                    @case(PaymentStatus::Succeeded)
                        <p>{{ $amount }} ушли на {{ $campaign ? 'сбор '.$target : $target }}. Когда приют потратит деньги, на странице {{ $campaign ? 'сбора' : 'приюта' }} появится отчёт с чеками.</p>
                        @break
                    @case(PaymentStatus::Pending)
                        @if ($waiting)
                            <p>Обычно это занимает до минуты. Страница обновится сама, закрывать её не нужно.</p>
                        @else
                            <p>Банк так и не подтвердил оплату. Если деньги списались, они вернутся на карту; если нет — попробуйте ещё раз.</p>
                        @endif
                        @break
                    @case(PaymentStatus::Failed)
                        <p>{{ $donation->failure_reason ?: 'Банк отклонил платёж. Деньги не списаны.' }} Попробуйте другую карту или меньшую сумму.</p>
                        @break
                    @case(PaymentStatus::Expired)
                        <p>Оплата не завершилась за {{ (int) config('payments.pending_ttl_minutes', 60) }} минут. Деньги не списаны — можно попробовать ещё раз.</p>
                        @break
                    @case(PaymentStatus::Refunded)
                        <p>{{ $amount }} вернутся на карту — обычно за несколько дней, по правилам банка.</p>
                        @break
                @endswitch
            </div>

            <x-badge :status="$status" />

            <div class="mt-4 flex flex-wrap justify-center gap-3">
                @switch($status)
                    @case(PaymentStatus::Succeeded)
                        <x-button icon="share" type="button" :data-share="$shareUrl" :data-share-title="$campaign?->title ?? $shelter->name">Поделиться {{ $campaign ? 'сбором' : 'приютом' }}</x-button>
                        <x-button variant="secondary" :href="route('pets.index')">Посмотреть других питомцев</x-button>
                        @break
                    @case(PaymentStatus::Pending)
                        @if ($waiting && $owner && $donation->provider === 'fake')
                            <x-button variant="donate" icon="heart" :href="route('payments.fake.show', $donation)">Перейти к оплате</x-button>
                        @elseif (! $waiting)
                            <x-button variant="donate" icon="heart" :href="$retryUrl">Попробовать снова</x-button>
                        @endif
                        <x-button variant="secondary" :href="$backUrl">{{ $backLabel }}</x-button>
                        @break
                    @case(PaymentStatus::Failed)
                    @case(PaymentStatus::Expired)
                        <x-button variant="donate" icon="heart" :href="$retryUrl">Попробовать снова</x-button>
                        <x-button variant="secondary" :href="$backUrl">{{ $backLabel }}</x-button>
                        @break
                    @default
                        <x-button variant="secondary" :href="$backUrl">{{ $backLabel }}</x-button>
                @endswitch
            </div>
        </section>
    </div>
</x-layouts.app>
