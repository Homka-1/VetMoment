<?php

namespace Tests\Unit\Support;

use App\Support\Estimate;
use PHPUnit\Framework\TestCase;

class EstimateTest extends TestCase
{
    public function test_lines_with_amounts_become_items(): void
    {
        $parsed = Estimate::parse("Ромашку сбила машина.\nНужна операция.\n\nСмета:\n— операция и наркоз — 380 000 ₸;\n- пластина и спицы - 120 000 ₸\n– стационар: 60 000 тг.\n\nСпасибо!");

        $this->assertSame(["Ромашку сбила машина.\nНужна операция.", 'Спасибо!'], $parsed['paragraphs']);
        $this->assertSame([
            ['label' => 'Операция и наркоз', 'amount' => 38_000_000],
            ['label' => 'Пластина и спицы', 'amount' => 12_000_000],
            ['label' => 'Стационар', 'amount' => 6_000_000],
        ], $parsed['items']);
        $this->assertSame(56_000_000, $parsed['total']);
    }

    public function test_text_without_estimate(): void
    {
        $parsed = Estimate::parse("Просто текст — без сумм.\n— пункт без суммы");

        $this->assertSame(["Просто текст — без сумм.\n— пункт без суммы"], $parsed['paragraphs']);
        $this->assertSame([], $parsed['items']);
        $this->assertSame(0, Estimate::parse(null)['total']);
    }
}
