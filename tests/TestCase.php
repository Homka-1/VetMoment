<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Тесты не зависят от собранных ассетов (public/build) и Vite dev server.
        $this->withoutVite();
    }
}
