<?php

namespace App\Enums;

/**
 * Общие методы enum-ов статусов: подписи для интерфейса и списки для форм/валидации.
 *
 * Enum, использующий трейт, реализует `label(): string`.
 */
trait HasLabel
{
    /**
     * Значения enum-а — для правил валидации `in:` и фильтров.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Пары «значение → подпись» для выпадающих списков.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
