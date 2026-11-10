<?php

namespace App\Enums;

/**
 * Вычисляемое состояние слота для интерфейса: свободен / мало мест / заполнен / запись закрыта / прошёл / отменён.
 */
enum WalkSlotState: string
{
    use HasLabel;

    case Available = 'available';
    case FewLeft = 'few_left';
    case Full = 'full';
    /** Слот ещё не начался, но запись уже закрыта (W1: меньше чем за config('walks.book_before_hours') ч). */
    case Closed = 'closed';
    case Past = 'past';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Свободен',
            self::FewLeft => 'Мало мест',
            self::Full => 'Мест нет',
            self::Closed => 'Запись закрыта',
            self::Past => 'Прошёл',
            self::Cancelled => 'Отменён',
        };
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Available => Tone::Success,
            self::FewLeft => Tone::Warning,
            self::Full, self::Closed, self::Past, self::Cancelled => Tone::Neutral,
        };
    }

    /** Можно ли выбрать слот для записи. */
    public function isBookable(): bool
    {
        return in_array($this, [self::Available, self::FewLeft], true);
    }
}
