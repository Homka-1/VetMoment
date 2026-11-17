<?php

namespace App\Notifications\Concerns;

use App\Models\WalkBooking;
use App\Support\LocalTime;

/**
 * Описание прогулки для текстов уведомлений: «суббота, 22 ноября, 10:00–11:00, «Дом лап»».
 */
trait DescribesWalk
{
    protected static function walkWhen(WalkBooking $booking): string
    {
        $slot = $booking->slot;

        return LocalTime::dayLong($slot->starts_at).', '.LocalTime::timeRange($slot->starts_at, $slot->ends_at);
    }

    protected static function walkWhere(WalkBooking $booking): string
    {
        return '«'.$booking->slot->shelter->name.'»';
    }

    /** «Собака: Барон.» или пусто, если собаку подберёт приют. */
    protected static function walkPet(WalkBooking $booking): string
    {
        return $booking->pet ? 'Собака: '.$booking->pet->name.'.' : '';
    }
}
