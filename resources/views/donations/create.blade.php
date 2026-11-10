@use('App\Support\Navigation')
{{-- Отдельная страница пожертвования: на сбор (/campaigns/{slug}/donate — блок формы без описания)
     или в общий фонд приюта (/shelters/{slug}/donate). --}}
@php
    $title = $campaign ? 'Помочь сбору' : 'Помочь приюту «'.$shelter->name.'»';
    $crumbs = $campaign
        ? ['Главная' => route('home'), 'Сборы' => route('campaigns.index'), $campaign->title => route('campaigns.show', $campaign), 'Пожертвование' => null]
        : ['Главная' => route('home'), 'Приюты' => Navigation::url('shelters.index', '/shelters'), $shelter->name => null];
@endphp
<x-layouts.app :title="$campaign ? 'Пожертвование — '.$campaign->title : $title" canvas>
    <x-slot:head><meta name="robots" content="noindex"></x-slot:head>
    <div class="page-container pt-6 pb-16 lg:pt-8 lg:pb-24">
        <x-breadcrumbs :items="$crumbs" />

        <div class="mx-auto mt-6 grid max-w-[560px] gap-6 lg:mt-10">
            <div>
                <h1 class="type-h2">{{ $campaign ? $campaign->title : $title }}</h1>
                <p class="mt-3 flex items-center gap-2 text-sm text-secondary"><x-icon name="home" size="sm" />{{ $shelter->name }}, {{ $shelter->city }}</p>
                @unless ($campaign)
                    <p class="mt-4 text-secondary">
                        Деньги уйдут в общий фонд приюта: на корм, вакцины и счета ветклиники. Приют сам решит, что срочнее,
                        и расскажет о расходах в отчётах.
                    </p>
                @endunless
            </div>

            <x-card padding="lg" class="grid gap-6">
                @if ($campaign)
                    <x-campaign-summary :campaign="$campaign" class="border-b border-subtle pb-6" />
                @endif
                <x-donation-form :action="$campaign ? route('campaigns.donate.store', $campaign) : route('shelters.donate.store', $shelter)" :prefill="$prefill" />
            </x-card>
        </div>
    </div>
</x-layouts.app>
