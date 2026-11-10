@use('App\Enums\BookingStatus')
@use('App\Http\Controllers\Shelter\WalkBookingController')
{{-- Кнопки решения по записи на выгул. $booking, $next — список допустимых BookingStatus.
     «Отклонить» и «Отменить запись» спрашивают причину в модальном окне — её увидит волонтёр. --}}
@php
    $withReason = [BookingStatus::Rejected, BookingStatus::Cancelled];
@endphp
<div class="flex justify-end gap-2 whitespace-nowrap">
    @foreach ($next as $status)
        @php $action = WalkBookingController::ACTIONS[$status->value]; @endphp
        @if (in_array($status, $withReason, true))
            <x-button variant="secondary" size="sm" type="button" data-modal-open="booking-{{ $booking->id }}-{{ $status->value }}">{{ $action['button'] }}</x-button>
        @else
            <form method="POST" action="{{ route('shelter.bookings.update', $booking) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $status->value }}">
                <x-button :variant="$status === BookingStatus::NoShow ? 'secondary' : 'primary'" size="sm">{{ $action['button'] }}</x-button>
            </form>
        @endif
    @endforeach
</div>
@foreach ($next as $status)
    @if (in_array($status, $withReason, true))
        @php $id = 'booking-'.$booking->id.'-'.$status->value; @endphp
        <x-modal :id="$id" :title="$status === BookingStatus::Rejected ? 'Отклонить запись?' : 'Отменить запись?'" class="text-left">
            <form method="POST" action="{{ route('shelter.bookings.update', $booking) }}" id="{{ $id }}-form" class="grid gap-4">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $status->value }}">
                <p>{{ $booking->user->name }} увидит причину в своей записи.</p>
                <x-textarea name="shelter_comment" :id="$id.'-comment'" label="Причина" required :maxlength="500" rows="3"
                            :value="''" placeholder="Например: на это время собака уходит к ветеринару" />
            </form>
            <x-slot:footer>
                <x-button variant="ghost" type="button" data-modal-close>Не {{ $status === BookingStatus::Rejected ? 'отклонять' : 'отменять' }}</x-button>
                <x-button variant="danger" :form="$id.'-form'">{{ WalkBookingController::ACTIONS[$status->value]['button'] }}</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
@endforeach
