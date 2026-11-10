@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
{{-- Все пожертвования сбора: имя или «Аноним», сумма, дата, комментарий (публичный список, roles.md). --}}
<x-layouts.app :title="'Пожертвования — '.$campaign->title">
    <x-slot:head><meta name="robots" content="noindex"></x-slot:head>
    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="['Главная' => route('home'), 'Сборы' => route('campaigns.index'), $campaign->title => route('campaigns.show', $campaign), 'Пожертвования' => null]" />

        <div class="mx-auto mt-6 max-w-narrow">
            <h1 class="type-h2">Кто помог сбору</h1>
            <p class="mt-2 text-secondary">
                «{{ $campaign->title }}»: {{ Plural::format((int) $campaign->donations_count, ['пожертвование', 'пожертвования', 'пожертвований']) }}
                на {{ Money::format($campaign->raised_amount) }}.
            </p>

            @if ($donations->isEmpty())
                <x-empty-state title="Пока никто не помог" illustration="dog-caramel" tone="tag" class="mt-8">
                    Станьте первым — даже 1 000 ₸ приближают цель.
                    <x-slot:action><x-button variant="donate" icon="heart" :href="route('campaigns.show', $campaign).'#donate'">Помочь сбору</x-button></x-slot:action>
                </x-empty-state>
            @else
                <ul class="mt-8 divide-y divide-subtle rounded-lg border border-subtle bg-raised px-4 sm:px-6">
                    @foreach ($donations as $donation)
                        <li class="flex items-start gap-3 py-4">
                            <x-avatar :name="$donation->is_anonymous || blank($donation->donor_name) ? '?' : $donation->donor_name" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ $donation->publicDonorName() }}</p>
                                <p class="text-xs text-muted"><time datetime="{{ LocalTime::iso($donation->paid_at ?? $donation->created_at) }}">{{ LocalTime::dateTime($donation->paid_at ?? $donation->created_at) }}</time></p>
                                @if ($donation->comment && ! $donation->is_anonymous)
                                    <p class="mt-1 text-sm break-words text-secondary">«{{ $donation->comment }}»</p>
                                @endif
                            </div>
                            <p class="shrink-0 font-semibold tabular-nums">{{ Money::format($donation->amount) }}</p>
                        </li>
                    @endforeach
                </ul>
                <x-pagination :paginator="$donations" class="mt-8" />
            @endif
        </div>
    </div>
</x-layouts.app>
