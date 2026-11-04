<?php

namespace App\Enums;

/**
 * Статус анкеты питомца.
 */
enum PetStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case LookingForHome = 'looking_for_home';
    case OnTreatment = 'on_treatment';
    case Reserved = 'reserved';
    case Adopted = 'adopted';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::LookingForHome => 'Ищет дом',
            self::OnTreatment => 'На лечении',
            self::Reserved => 'Забронирован',
            self::Adopted => 'Нашёл дом',
            self::Archived => 'В архиве',
        };
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Draft => Tone::Neutral,
            self::LookingForHome => Tone::Info,
            self::OnTreatment => Tone::Warning,
            self::Reserved => Tone::Neutral,
            self::Adopted => Tone::Success,
            self::Archived => Tone::Neutral,
        };
    }

    /**
     * Статусы, видимые публично (при одобренном приюте и is_hidden = false).
     * «Нашёл дом» показывается отдельным блоком, не в основном каталоге.
     *
     * @return list<self>
     */
    public static function public(): array
    {
        return [self::LookingForHome, self::OnTreatment, self::Reserved, self::Adopted];
    }

    /**
     * Статусы основного каталога.
     *
     * @return list<self>
     */
    public static function catalog(): array
    {
        return [self::LookingForHome, self::OnTreatment, self::Reserved];
    }

    /**
     * Статусы, в которых с питомцем можно гулять (правило W8).
     *
     * @return list<self>
     */
    public static function walkable(): array
    {
        return [self::LookingForHome, self::OnTreatment, self::Reserved];
    }

    /** Черновик и архив рисуются бейджем с пунктирной рамкой. */
    public function isDashed(): bool
    {
        return in_array($this, [self::Draft, self::Archived], true);
    }
}
