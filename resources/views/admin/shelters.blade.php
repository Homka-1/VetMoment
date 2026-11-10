@use('App\Http\Controllers\Admin\ShelterController')
@use('App\Support\LocalTime')
@use('App\Support\Phone')
{{-- Приюты: очередь модерации (макет 11-shelters). Отклонить и заблокировать — с причиной в модальном окне. --}}
<x-layouts.cabinet title="Приюты">
    <div class="grid grid-cols-1 gap-6">
        <x-tabs label="Статусы приютов" :items="collect(ShelterController::TABS)->map(fn ($t, $key) => [
            'label' => $t['label'],
            'href' => route('admin.shelters.index', ['status' => $key]),
            'active' => $tab === $key,
            'count' => $counts[$key],
        ])->values()->all()" />

        @if ($shelters->isEmpty())
            <x-empty-state :title="$tab === 'pending' ? 'Очередь пуста' : 'Здесь пока никого нет'" illustration="dog-caramel">
                {{ $tab === 'pending' ? 'Новые заявки приютов появятся здесь — их нужно одобрить или отклонить с причиной.' : 'Приюты в этом статусе появятся в списке.' }}
            </x-empty-state>
        @else
            <x-table caption="Приюты">
                <x-slot:head>
                    <tr>
                        <x-table.th>Приют</x-table.th>
                        <x-table.th>Заявитель</x-table.th>
                        <x-table.th>БИН / ИИН</x-table.th>
                        <x-table.th>Подана</x-table.th>
                        <x-table.th>Статус</x-table.th>
                        <x-table.th><span class="sr-only">Действия</span></x-table.th>
                    </tr>
                </x-slot:head>
                @foreach ($shelters as $shelter)
                    @php $owner = $shelter->owners->first(); @endphp
                    <tr>
                        <td class="min-w-52">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-pill bg-ink-100 text-ink-700"><x-icon name="home" /></span>
                                <div>
                                    @if ($shelter->isApproved())
                                        <a href="{{ route('shelters.show', $shelter) }}" class="link font-semibold">{{ $shelter->name }}</a>
                                    @else
                                        <p class="font-semibold">{{ $shelter->name }}</p>
                                    @endif
                                    <p class="text-xs text-secondary">{{ $shelter->city }}@if ($shelter->pets_count), анкет: {{ $shelter->pets_count }}@endif</p>
                                </div>
                            </div>
                        </td>
                        <td class="min-w-44">
                            @if ($owner)
                                <p class="font-semibold">{{ $owner->name }}</p>
                                <p class="text-xs text-secondary">{{ Phone::format($owner->phone) }}</p>
                            @else
                                —
                            @endif
                        </td>
                        <td class="tabular-nums">{{ $shelter->bin_iin ?: '—' }}</td>
                        <td class="whitespace-nowrap">{{ LocalTime::date($shelter->created_at) }}</td>
                        <td>
                            <x-badge :status="$shelter->status" />
                            @if ($shelter->moderation_comment)
                                <p class="mt-1 max-w-56 text-xs text-secondary">{{ $shelter->moderation_comment }}</p>
                            @endif
                        </td>
                        <td>
                            <div class="flex justify-end gap-2 whitespace-nowrap">
                                @can('approve', $shelter)
                                    <form method="POST" action="{{ route('admin.shelters.approve', $shelter) }}">@csrf<x-button size="sm" icon="check">Одобрить</x-button></form>
                                @endcan
                                @can('reject', $shelter)
                                    <x-button variant="secondary" size="sm" type="button" data-modal-open="reject-{{ $shelter->id }}">Отклонить</x-button>
                                @endcan
                                @can('block', $shelter)
                                    <x-button variant="secondary" size="sm" type="button" data-modal-open="block-{{ $shelter->id }}">Заблокировать</x-button>
                                @endcan
                                @can('unblock', $shelter)
                                    <form method="POST" action="{{ route('admin.shelters.unblock', $shelter) }}">@csrf<x-button variant="secondary" size="sm">Разблокировать</x-button></form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <x-pagination :paginator="$shelters" />

            @if ($tab === 'pending')
                <x-alert tone="info">Проверьте БИН/ИИН в реестре и документы. Приют без документов можно одобрить только после звонка заявителю.</x-alert>
            @endif
        @endif
    </div>

    @foreach ($shelters as $shelter)
        @foreach (['reject' => ['Отклонить заявку?', 'Отклонить', 'Заявитель увидит причину и сможет исправить заявку.'], 'block' => ['Заблокировать приют?', 'Заблокировать', 'Анкеты и сборы приюта пропадут с сайта. Причину увидит владелец приюта.']] as $action => [$title, $button, $text])
            @continue(! \Illuminate\Support\Facades\Gate::allows($action, $shelter))
            <x-modal :id="$action.'-'.$shelter->id" :title="$title">
                <form method="POST" action="{{ route('admin.shelters.'.$action, $shelter) }}" id="{{ $action }}-{{ $shelter->id }}-form" class="grid gap-4">
                    @csrf
                    <p>{{ $text }}</p>
                    <x-textarea name="reason" :id="$action.'-'.$shelter->id.'-reason'" label="Причина" rows="3" maxlength="1000" required :value="''" />
                </form>
                <x-slot:footer>
                    <x-button variant="ghost" type="button" data-modal-close>Отмена</x-button>
                    <x-button variant="danger" :form="$action.'-'.$shelter->id.'-form'">{{ $button }}</x-button>
                </x-slot:footer>
            </x-modal>
        @endforeach
    @endforeach
</x-layouts.cabinet>
