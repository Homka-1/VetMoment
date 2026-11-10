@use('App\Support\Money')
{{-- Демо-страница «банка» (FakePaymentGateway). В настоящем режиме здесь была бы страница провайдера. --}}
<x-layouts.app title="Оплата пожертвования — демо" canvas>
    <x-slot:head><meta name="robots" content="noindex"></x-slot:head>
    <div class="page-container py-10 lg:py-20">
        <section class="mx-auto grid max-w-[480px] gap-6 rounded-lg border border-subtle bg-raised p-6 sm:p-8" aria-labelledby="bank-title">
            <x-alert tone="warning" title="Учебная страница банка">
                Это демо-режим: карта не нужна, настоящие деньги не списываются. Выберите, чем закончится платёж.
            </x-alert>

            <div>
                <h1 id="bank-title" class="type-h3">Оплата картой</h1>
                <dl class="mt-4 divide-y divide-subtle text-sm">
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-secondary">Получатель</dt>
                        <dd class="text-right font-semibold">{{ $donation->shelter->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-secondary">Назначение</dt>
                        <dd class="text-right font-semibold">{{ $donation->purposeLabel() }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 py-2.5">
                        <dt class="text-secondary">Сумма</dt>
                        <dd class="type-number">{{ Money::format($donation->amount) }}</dd>
                    </div>
                </dl>
            </div>

            <form method="POST" action="{{ route('payments.fake.pay', $donation) }}" class="grid gap-3">
                @csrf
                <x-button name="outcome" value="success" size="lg" icon="check" block>Оплатить {{ Money::format($donation->amount) }}</x-button>
                <x-button name="outcome" value="decline" variant="secondary" block>Отказ банка</x-button>
            </form>

            <a href="{{ route('donations.show', $donation) }}" class="justify-self-center text-sm text-secondary underline underline-offset-3 hover:text-primary">Закрыть страницу без оплаты</a>
        </section>
    </div>
</x-layouts.app>
