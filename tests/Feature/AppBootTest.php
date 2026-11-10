<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppBootTest extends TestCase
{
    use RefreshDatabase; // главная читает данные из БД

    public function test_home_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('VetMoment')
            ->assertSee('lang="ru"', escape: false);
    }

    public function test_health_check_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_locale_and_timezones_are_configured(): void
    {
        $this->assertSame('ru', app()->getLocale());
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('Asia/Almaty', config('app.display_timezone'));
    }
}
