<?php

namespace App\Http\Controllers\Walks;

use App\Http\Controllers\Controller;
use App\Models\WalkBooking;
use App\Services\WalkBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Запись волонтёра (макет 06-success): статус, детали, «Добавить в календарь», отмена.
 */
class BookingController extends Controller
{
    public function show(WalkBooking $booking, WalkBookingService $service): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['slot.shelter', 'pet.cover']);

        return view('walks.booking', [
            'booking' => $booking,
            'slot' => $booking->slot,
            'shelter' => $booking->slot->shelter,
            'canCancel' => Gate::allows('cancel', $booking) && $service->userCanCancelInTime($booking),
            'cancelHours' => $service->hours('walks.cancel_before_hours'),
        ]);
    }

    public function cancel(WalkBooking $booking, WalkBookingService $service): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        $service->cancelByUser($booking);

        return redirect()->route('walks.bookings.show', $booking)->with('status', 'Запись отменена');
    }

    /** Файл .ics для «Добавить в календарь» (RFC 5545), время — UTC. */
    public function calendar(WalkBooking $booking): Response
    {
        Gate::authorize('view', $booking);

        $booking->load(['slot.shelter', 'pet']);
        $slot = $booking->slot;
        $shelter = $slot->shelter;
        $escape = fn (?string $text): string => str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $text);
        $title = 'Выгул в приюте «'.$shelter->name.'»'.($booking->pet ? ' — '.$booking->pet->name : '');
        $location = $slot->meeting_point ?: trim($shelter->city.', '.$shelter->address, ', ');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//VetMoment//Walks//RU',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:walk-booking-'.$booking->id.'@'.(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'vetmoment'),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$slot->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$slot->ends_at->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape($title),
            'LOCATION:'.$escape($location),
            'DESCRIPTION:'.$escape('Запись: '.route('walks.bookings.show', $booking)),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="vetmoment-walk-'.$booking->id.'.ics"',
        ]);
    }
}
