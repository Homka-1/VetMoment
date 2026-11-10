@use('App\Enums\AttachmentType')
@use('App\Support\Estimate')
@use('App\Support\LocalTime')
@use('App\Support\Money')
@use('App\Support\Plural')
@props([
    'report',
    'headingLevel' => 3,
])
@php
    // Отчёт приюта о расходах (макет 05): дата, заголовок, текст, строки расходов, фото и чеки, итого.
    // Нужна загруженная связь attachments.
    $parsed = Estimate::parse($report->body);
    $total = $report->spent_amount ?? ($parsed['items'] ? $parsed['total'] : null);
    $attachments = $report->attachments;
    $receipts = $attachments->where('type', AttachmentType::Receipt)->count();
    $photos = $attachments->count() - $receipts;
    $counts = collect([
        $photos ? Plural::format($photos, ['фото', 'фото', 'фото']) : null,
        $receipts ? Plural::format($receipts, ['чек', 'чека', 'чеков']) : null,
    ])->filter()->implode(' и ');
@endphp
<article {{ $attributes->class('rounded-lg border border-subtle bg-raised p-6') }}>
    @if ($report->published_at)
        <p class="text-xs text-muted"><time datetime="{{ LocalTime::iso($report->published_at) }}">{{ LocalTime::date($report->published_at) }}</time></p>
    @endif
    <h{{ $headingLevel }} class="mt-1 text-md font-semibold">{{ $report->title }}</h{{ $headingLevel }}>

    @foreach ($parsed['paragraphs'] as $paragraph)
        <p class="mt-3 whitespace-pre-line text-secondary">{{ $paragraph }}</p>
    @endforeach

    @if ($parsed['items'])
        <x-estimate-table :items="$parsed['items']" class="mt-3" />
    @endif

    @if ($attachments->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2" aria-label="Фото и чеки">
            @foreach ($attachments as $attachment)
                <li>
                    <a href="{{ $attachment->url() }}" target="_blank" rel="noopener"
                       class="block size-16 overflow-hidden rounded-sm border border-subtle bg-ink-100 md:size-20">
                        <img src="{{ $attachment->url() }}" alt="{{ $attachment->type->label() }} {{ $loop->iteration }} к отчёту «{{ $report->title }}»"
                             loading="lazy" decoding="async" class="size-full object-cover">
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($counts !== '' || $total !== null)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-subtle pt-4 text-sm">
            <span class="flex items-center gap-2 text-secondary">
                @if ($counts !== '')
                    <x-icon name="image" size="sm" />{{ $counts }}
                @endif
            </span>
            @if ($total !== null)
                <span class="font-semibold tabular-nums">Потрачено {{ Money::format($total) }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}
</article>
