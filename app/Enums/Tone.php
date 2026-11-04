<?php

namespace App\Enums;

/**
 * Тон бейджа статуса из UI-кита (docs/design/tokens.md, «Подписи статусов»).
 * На этапе 7 тон превращается в набор классов компонента `<x-badge>`.
 */
enum Tone: string
{
    case Neutral = 'neutral';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
    case Tag = 'tag';
    case Meadow = 'meadow';
}
