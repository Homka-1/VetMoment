@use('App\Enums\CampaignStatus')
@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
{{-- Страница сбора (макет 05): обложка, описание и смета слева; справа — цифры, форма пожертвования
     и последние пожертвования; ниже — отчёты приюта. На телефоне форма идёт после описания,
     а кнопка «Помочь сбору» прижата к низу экрана. --}}
@php
    $shelter = $campaign->shelter;
    $pet = $campaign->pet;
    $isPublic = ! $campaign->is_hidden && in_array($campaign->status, CampaignStatus::public(), true) && $shelter->isApproved();
    $image = $campaign->coverUrl();
    $art = $campaign->illustration();
    $badge = $campaign->isGoalReached() ? CampaignStatus::Completed
        : ($campaign->status === CampaignStatus::Active && ! $campaign->acceptsDonations() ? CampaignStatus::Closed : $campaign->status);
    $petVisible = $pet && Gate::allows('view', $pet);
    $description = Str::limit($campaign->short_description, 155);
    $when = function ($moment): string {
        $local = LocalTime::of($moment);

        return $local->greaterThan(now()->subDay()) ? $local->diffForHumans() : LocalTime::date($moment);
    };
@endphp
<x-layouts.app :title="$campaign->title.' — сбор приюта «'.$shelter->name.'»'" :description="$description">
    <x-slot:head>
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $campaign->title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ route('campaigns.show', $campaign) }}">
        @if ($image)
            <meta property="og:image" content="{{ $image }}">
        @endif
        @unless ($isPublic)
            <meta name="robots" content="noindex">
        @endunless
    </x-slot:head>

    <div @class(['page-container pt-6 lg:pt-8 lg:pb-24', 'pb-28' => $canDonate, 'pb-16' => ! $canDonate])>
        <x-breadcrumbs :items="['Главная' => route('home'), 'Сборы' => route('campaigns.index'), $campaign->title => null]" />

        @unless ($isPublic)
            <x-alert tone="warning" title="Сбор не виден на сайте" class="mt-6">
                {{ collect([
                    'Статус «'.$campaign->status->label().'»',
                    $campaign->is_hidden ? 'сбор скрыт модератором' : null,
                    $shelter->isApproved() ? null : 'приют ещё не прошёл проверку',
                ])->filter()->implode(', ') }}.
                Эту страницу видят только сотрудники приюта и администраторы.
            </x-alert>
        @endunless

        <div class="mt-6 grid gap-8 lg:mt-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,472px)] lg:grid-rows-[auto_1fr] lg:gap-x-12 lg:gap-y-16">
            {{-- О сборе --}}
            <div class="grid min-w-0 content-start gap-6 lg:col-start-1">
                <div @class(['relative aspect-[16/10] overflow-hidden rounded-lg md:aspect-[19/10]', $image ? 'bg-ink-100' : $art['bg']])>
                    @if ($image)
                        <img src="{{ $image }}" alt="{{ $pet ? $pet->name.' — '.$campaign->title : '' }}" class="size-full object-cover">
                    @else
                        <img src="{{ $art['src'] }}" alt="" class="absolute inset-x-0 bottom-0 mx-auto h-[86%] w-auto">
                    @endif
                    <x-badge :status="$badge" @class(['absolute top-4 left-4', 'border-0 bg-white! shadow-sm' => $badge !== CampaignStatus::Active]) />
                </div>

                <div>
                    <h1 class="type-h2 break-words">{{ $campaign->title }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-x-8 gap-y-2 text-sm">
                        <span class="flex items-center gap-2 text-secondary"><x-icon name="home" size="sm" />{{ $shelter->name }}, {{ $shelter->city }}</span>
                        @if ($petVisible)
                            <a href="{{ route('pets.show', $pet) }}" class="inline-flex items-center gap-1 font-semibold text-primary underline underline-offset-3 hover:decoration-2">
                                Анкета: {{ $pet->name }}<x-icon name="chevron-right" size="sm" />
                            </a>
                        @endif
                    </div>
                </div>

                <x-campaign-summary :campaign="$campaign" class="lg:hidden" />

                <div class="grid max-w-prose gap-4 text-md text-secondary">
                    @foreach ($estimate['paragraphs'] as $paragraph)
                        <p class="whitespace-pre-line">{{ $paragraph }}</p>
                    @endforeach
                </div>

                @if ($estimate['items'])
                    <section aria-labelledby="estimate-title" class="mt-4">
                        <h2 id="estimate-title" class="type-h3">На что нужны деньги</h2>
                        <x-estimate-table :items="$estimate['items']" class="mt-4 text-md"
                                          :total="count($estimate['items']) > 1 && $estimate['total'] !== $campaign->goal_amount ? $estimate['total'] : null" />
                    </section>
                @endif
            </div>

            {{-- Пожертвовать --}}
            <aside class="grid min-w-0 content-start gap-8 lg:col-start-2 lg:row-span-2 lg:row-start-1" aria-label="Пожертвование">
                <div id="donate" class="-mx-4 scroll-mt-24 bg-canvas px-4 py-8 sm:mx-0 sm:rounded-lg sm:border sm:border-subtle sm:bg-raised sm:p-8 lg:shadow-md">
                    <x-campaign-summary :campaign="$campaign" class="hidden border-b border-subtle pb-6 lg:grid" />

                    @if ($canManage)
                        <div class="mb-6 grid gap-2 sm:grid-cols-2 lg:mt-6 lg:mb-0">
                            <x-button variant="secondary" size="sm" icon="edit" :href="route('shelter.campaigns.edit', $campaign)">Редактировать</x-button>
                            <x-button variant="secondary" size="sm" icon="image" :href="route('shelter.campaigns.reports.index', $campaign)">Отчёты</x-button>
                        </div>
                    @endif

                    @if ($canDonate)
                        <h2 class="type-h3 lg:mt-6">Пожертвовать</h2>
                        @if ($campaign->status === CampaignStatus::Completed)
                            <p class="mt-3 text-sm text-secondary">
                                Цель уже собрана. Приют принимает помощь сверх цели{{ $campaign->ends_at ? ' до '.LocalTime::date($campaign->ends_at) : '' }} — остаток уйдёт на реабилитацию и другие нужды.
                            </p>
                        @endif
                        <x-donation-form :action="route('campaigns.donate.store', $campaign)" :prefill="$prefill" class="mt-6" />
                    @else
                        <div class="grid gap-4 lg:mt-6">
                            <h2 class="type-h4">{{ $campaign->isGoalReached() ? 'Цель собрана — спасибо!' : 'Сбор закрыт' }}</h2>
                            <p class="text-secondary">
                                @if ($campaign->isGoalReached())
                                    Приют получил всю сумму. Отчёт о расходах появится на этой странице.
                                @else
                                    Пожертвования на этот сбор больше не принимаются. Помочь приюту можно в общий фонд — на корм и лечение.
                                @endif
                            </p>
                            @can('donate', $shelter)
                                <x-button variant="donate" icon="heart" :href="route('shelters.donate', $shelter)" block>Помочь приюту</x-button>
                            @endcan
                            <x-button variant="secondary" :href="route('campaigns.index')" block>Другие сборы</x-button>
                        </div>
                    @endif
                </div>

                @if ($recent->isNotEmpty())
                    <section aria-labelledby="recent-title">
                        <h2 id="recent-title" class="text-md font-semibold">Последние пожертвования</h2>
                        <ul class="mt-3 divide-y divide-subtle">
                            @foreach ($recent as $donation)
                                <li class="flex items-start gap-3 py-3">
                                    <x-avatar :name="$donation->is_anonymous || blank($donation->donor_name) ? '?' : $donation->donor_name" />
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold">{{ $donation->publicDonorName() }}</p>
                                        <p class="text-xs text-muted"><time datetime="{{ LocalTime::iso($donation->paid_at ?? $donation->created_at) }}">{{ $when($donation->paid_at ?? $donation->created_at) }}</time></p>
                                        @if ($donation->comment && ! $donation->is_anonymous)
                                            <p class="mt-1 text-sm break-words text-secondary">«{{ $donation->comment }}»</p>
                                        @endif
                                    </div>
                                    <p class="shrink-0 font-semibold tabular-nums">{{ Money::format($donation->amount) }}</p>
                                </li>
                            @endforeach
                        </ul>
                        @if ($campaign->donations_count > $recent->count())
                            <a href="{{ route('campaigns.donations', $campaign) }}" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold underline underline-offset-3 hover:decoration-2">
                                Все {{ Plural::format((int) $campaign->donations_count, ['пожертвование', 'пожертвования', 'пожертвований']) }}<x-icon name="chevron-right" size="sm" />
                            </a>
                        @endif
                    </section>
                @endif
            </aside>

            {{-- Отчёты --}}
            <section aria-labelledby="reports-title" class="grid min-w-0 content-start gap-6 lg:col-start-1 lg:row-start-2">
                <h2 id="reports-title" class="type-h3">Отчёты приюта</h2>
                @forelse ($campaign->reports as $report)
                    <x-campaign-report :report="$report" />
                @empty
                    <p class="rounded-lg border border-dashed border-strong p-6 text-secondary">
                        Когда приют потратит деньги, здесь появится отчёт: на что ушла каждая сумма, с фото покупок и чеками.
                    </p>
                @endforelse
            </section>
        </div>
    </div>

    @if ($canDonate)
        <x-sticky-bar>
            <x-button variant="donate" icon="heart" href="#donate">Помочь сбору</x-button>
        </x-sticky-bar>
    @endif
</x-layouts.app>
