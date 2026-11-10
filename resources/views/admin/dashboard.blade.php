@use('App\Support\Money')
@use('App\Support\Plural')
{{-- Статистика администратора (макет 11-stats): итоги месяца и прогулки по неделям. --}}
<x-layouts.cabinet title="Статистика">
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.dashboard') }}" data-autosubmit class="flex items-center gap-2">
            <label for="f-month" class="sr-only">Месяц</label>
            <x-select name="month" :options="collect($months)->pluck('label', 'value')->all()" :value="$month->format('Y-m')" />
            <x-button variant="secondary" data-autosubmit-fallback>Показать</x-button>
        </form>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-8">
        @if ($pendingShelters > 0)
            <x-alert tone="warning" title="Приюты ждут проверки">
                {{ Plural::format($pendingShelters, ['заявка приюта', 'заявки приютов', 'заявок приютов']) }} на модерации.
                <x-slot:action><x-button variant="secondary" size="sm" :href="route('admin.shelters.index')">Открыть очередь</x-button></x-slot:action>
            </x-alert>
        @endif

        <section aria-label="Итоги месяца" class="grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
            <x-stat label="Пристроено" :value="$data['adopted']"
                    :hint="$adoptedDelta === 0 ? 'как в прошлом месяце' : ($adoptedDelta > 0 ? '+' : '−').abs($adoptedDelta).' к прошлому месяцу'"
                    :hint-tone="$adoptedDelta > 0 ? 'success' : 'muted'" />
            <x-stat label="Собрано" :value="Money::compact($data['raised'])" :hint="Plural::format($data['donations'], ['пожертвование', 'пожертвования', 'пожертвований'])" tone="donate" />
            <x-stat label="Прогулок" :value="$data['walks']" :hint="'в '.Plural::format($data['shelters'], ['приюте', 'приютах', 'приютах'])" />
            <x-stat label="Неявки" :value="str_replace('.', ',', (string) $data['noShowRate']).'%'" :hint="$data['noShows'].' из '.($data['walks'] + $data['noShows'])" />
        </section>

        <x-card padding="lg" as="section" aria-labelledby="weeks-heading">
            <h2 id="weeks-heading" class="type-h4">Прогулки по неделям</h2>
            @php $max = max(1, collect($data['weeks'])->max('count')); @endphp
            @if (collect($data['weeks'])->sum('count') === 0)
                <p class="mt-4 text-secondary">В этом месяце не было состоявшихся прогулок.</p>
            @else
                <ol class="mt-8 flex h-56 items-end gap-3 border-b border-subtle md:gap-8">
                    @foreach ($data['weeks'] as $week)
                        <li class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                            <span class="text-sm font-semibold tabular-nums">{{ $week['count'] }}<span class="sr-only"> прогулок</span></span>
                            <span class="w-full max-w-16 rounded-t-sm bg-meadow-500" style="height: {{ max(2, round($week['count'] / $max * 85)) }}%"></span>
                        </li>
                    @endforeach
                </ol>
                <ol class="mt-2 flex gap-3 md:gap-8" aria-label="Недели, начало с понедельника">
                    @foreach ($data['weeks'] as $week)
                        <li class="min-w-0 flex-1 text-center text-xs text-muted">{{ $week['label'] }}</li>
                    @endforeach
                </ol>
            @endif
        </x-card>

        <section aria-labelledby="overall-heading">
            <h2 id="overall-heading" class="type-h4">За всё время</h2>
            <dl class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="rounded-lg border border-subtle bg-raised p-4"><dt class="text-sm text-secondary">Питомцев нашли дом</dt><dd class="mt-1 text-lg font-semibold">{{ $overall['adopted'] }}</dd></div>
                <div class="rounded-lg border border-subtle bg-raised p-4"><dt class="text-sm text-secondary">Собрано</dt><dd class="mt-1 text-lg font-semibold">{{ Money::compact($overall['raised']) }}</dd></div>
                <div class="rounded-lg border border-subtle bg-raised p-4"><dt class="text-sm text-secondary">Прогулок</dt><dd class="mt-1 text-lg font-semibold">{{ $overall['walks'] }}</dd></div>
                <div class="rounded-lg border border-subtle bg-raised p-4"><dt class="text-sm text-secondary">Проверенных приютов</dt><dd class="mt-1 text-lg font-semibold">{{ $overall['shelters'] }}</dd></div>
            </dl>
        </section>
    </div>
</x-layouts.cabinet>
