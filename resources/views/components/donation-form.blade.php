@use('App\Support\Money')
@props([
    'action',
    'prefill' => [],
    'idPrefix' => 'donation',
])
@php
    // Форма пожертвования (макет 05): пресеты и своя сумма, email для квитанции, имя, «не показывать имя»,
    // комментарий, согласие с офертой. Ключ идемпотентности — новый на каждую отрисовку формы,
    // а после ошибки валидации прежний (old) — повторная отправка той же попытки не создаст второй платёж.
    $amount = old('amount', $prefill['amount'] ?? null);
    $amountValue = is_numeric(str_replace([' ', "\u{00A0}"], '', (string) $amount)) ? (int) str_replace([' ', "\u{00A0}"], '', (string) $amount) : null;
    $demo = config('payments.default') === 'fake';
@endphp
<form method="POST" action="{{ $action }}" novalidate {{ $attributes->class('grid gap-6') }} data-donation-form>
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">

    <x-amount-picker name="amount" label="Сумма" :value="$amountValue" :presets="config('payments.presets')"
                     :min="config('payments.min_amount')" :max="config('payments.max_amount')" />

    <x-input name="donor_email" type="email" label="Email для квитанции" :value="$prefill['donor_email'] ?? null"
             required autocomplete="email" inputmode="email" maxlength="255" />

    <div class="grid gap-3">
        <x-input name="donor_name" label="Имя" :value="$prefill['donor_name'] ?? null" autocomplete="given-name" maxlength="100"
                 hint="Покажем в списке пожертвований" />
        <x-checkbox name="is_anonymous" :checked="$prefill['is_anonymous'] ?? false">Не показывать моё имя</x-checkbox>
    </div>

    <x-textarea name="comment" label="Комментарий" :value="$prefill['comment'] ?? null" rows="2" maxlength="500"
                hint="Необязательно. Увидят все, если имя не скрыто" />

    <x-checkbox name="agree">Принимаю условия оферты и согласие на обработку данных</x-checkbox>

    <div class="grid gap-3">
        <x-button variant="donate" size="lg" icon="heart" block data-amount-submit>
            <span data-amount-label data-label-base="Пожертвовать">Пожертвовать{{ $amountValue ? ' '.Money::format($amountValue * 100) : '' }}</span>
        </x-button>
        <p class="flex items-start gap-2 text-xs text-muted">
            <x-icon name="shield-check" size="sm" class="mt-px shrink-0" />
            @if ($demo)
                <span>Демо-режим: откроется учебная страница банка, настоящие деньги не списываются.</span>
            @else
                <span>Оплата картой через защищённую страницу банка</span>
            @endif
        </p>
    </div>
</form>
