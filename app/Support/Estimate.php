<?php

namespace App\Support;

/**
 * Смета в свободном тексте сбора или отчёта → абзацы + строки таблицы «На что нужны деньги».
 *
 * Строка сметы — «— операция и наркоз — 380 000 ₸» (тире или дефис в начале, сумма в тенге в конце).
 * Приюту не нужно заполнять отдельную таблицу: он пишет текст, сайт показывает смету таблицей.
 * Заголовок «Смета:» перед строками убирается, остальной текст разбивается на абзацы.
 */
final class Estimate
{
    private const LINE = '/^\s*[—–-]\s*(?<label>.+?)\s*[—–:-]\s*(?<amount>\d[\d\s\x{00A0}]*)\s*(?:₸|тг\.?|тенге)\s*[.;,]?\s*$/u';

    private const HEADER = '/^\s*(смета|расходы|на что нужны деньги)\s*:?\s*$/iu';

    /**
     * Суммы amount и total — в тиынах.
     *
     * @return array{paragraphs: list<string>, items: list<array{label: string, amount: int}>, total: int}
     */
    public static function parse(?string $text): array
    {
        $paragraphs = [];
        $items = [];
        $buffer = [];

        $flush = function () use (&$buffer, &$paragraphs): void {
            $paragraph = trim(implode("\n", $buffer));
            if ($paragraph !== '') {
                $paragraphs[] = $paragraph;
            }
            $buffer = [];
        };

        foreach (preg_split('/\R/u', (string) $text) as $line) {
            if (preg_match(self::LINE, $line, $m)) {
                $flush();
                $items[] = [
                    'label' => self::capitalize(trim($m['label'])),
                    'amount' => (int) preg_replace('/\D/u', '', $m['amount']) * 100,
                ];
            } elseif (preg_match(self::HEADER, $line)) {
                $flush();
            } elseif (trim($line) === '') {
                $flush();
            } else {
                $buffer[] = trim($line);
            }
        }

        $flush();

        return [
            'paragraphs' => $paragraphs,
            'items' => $items,
            'total' => array_sum(array_column($items, 'amount')),
        ];
    }

    private static function capitalize(string $value): string
    {
        return mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }
}
