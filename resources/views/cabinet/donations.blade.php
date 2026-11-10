@use('App\Enums\PaymentStatus')
@use('App\Support\LocalTime')
@use('App\Support\Money')
{{-- «Мои пожертвования»: история, суммы, статусы; итог — только оплаченные. --}}
<x-layouts.cabinet title="Мои пожертвования">
    <div class="grid grid-cols-1 gap-6">
        @if ($donations->isEmpty())
            <x-empty-state title="Здесь будет история ваших пожертвований" illustration="dog-caramel" tone="tag">
                Помогите сбору на лечение или корм — сумма и статус платежа появятся в этом списке.
                <x-slot:action><x-button variant="donate" icon="heart" :href="route('campaigns.index')">Смотреть сборы</x-button></x-slot:action>
            </x-empty-state>
        @else
            <x-card class="flex flex-wrap items-baseline justify-between gap-3">
                <p class="text-secondary">Вы помогли приютам на</p>
                <p class="type-number">{{ Money::format($total) }}</p>
            </x-card>

            <x-table caption="Мои пожертвования">
                <x-slot:head>
                    <tr>
                        <x-table.th>Дата</x-table.th>
                        <x-table.th>Куда</x-table.th>
                        <x-table.th align="right">Сумма</x-table.th>
                        <x-table.th>Статус</x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($donations as $donation)
                    <tr>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('donations.show', $donation) }}" class="link">
                                <time datetime="{{ LocalTime::iso($donation->created_at) }}">{{ LocalTime::dateTime($donation->created_at) }}</time>
                            </a>
                        </td>
                        <td class="min-w-60">
                            @if ($donation->campaign && Gate::allows('view', $donation->campaign))
                                <a href="{{ route('campaigns.show', $donation->campaign) }}" class="font-semibold text-primary hover:underline">{{ $donation->campaign->title }}</a>
                            @else
                                <span class="font-semibold">{{ $donation->purposeLabel() }}</span>
                            @endif
                            <p class="text-xs text-secondary">{{ $donation->shelter->name }}</p>
                        </td>
                        <td class="text-right font-semibold whitespace-nowrap tabular-nums">{{ Money::format($donation->amount) }}</td>
                        <td>
                            <x-badge :status="$donation->status" />
                            @if ($donation->is_anonymous && $donation->status === PaymentStatus::Succeeded)
                                <p class="mt-1 text-xs text-muted">имя скрыто</p>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <x-pagination :paginator="$donations" />
        @endif
    </div>
</x-layouts.cabinet>
