<?php

namespace Tests\Feature\Ui;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Этап 13: фирменные страницы ошибок (макет 12-states).
 */
class ErrorPagesTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_unknown_url_shows_branded_404(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('Такой страницы нет')
            ->assertSee('Смотреть питомцев')
            ->assertSee('content="noindex, nofollow"', false);
    }

    public function test_missing_pet_shows_branded_404_with_signed_in_header(): void
    {
        // Ненайденная модель — внутри web-маршрута: шапка видит вошедшего пользователя.
        $this->actingAs($this->volunteer(['name' => 'Ерлан Проверочный']))
            ->get('/pets/nobody-here')
            ->assertNotFound()
            ->assertSee('Такой страницы нет')
            ->assertSee('Ерлан Проверочный');
    }

    public function test_forbidden_page_suggests_cabinet_or_login(): void
    {
        $this->actingAs($this->volunteer())->get('/admin')
            ->assertForbidden()
            ->assertSee('Этот раздел вам недоступен')
            ->assertSee('В личный кабинет');
    }

    public function test_server_error_page_does_not_touch_session_user(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/__boom', fn () => throw new \RuntimeException('boom'));

        $this->actingAs($this->volunteer(['name' => 'Айгерим Проверочная']))
            ->get('/__boom')
            ->assertStatus(500)
            ->assertSee('Сайт временно не отвечает')
            ->assertSee('Обновить страницу')
            ->assertDontSee('Айгерим Проверочная')
            ->assertSee('Войти');
    }

    public function test_page_expired_and_too_many_requests_are_branded(): void
    {
        Route::middleware('web')->get('/__expired', fn () => abort(419));
        Route::middleware('web')->get('/__throttled', fn () => abort(429));

        $this->get('/__expired')->assertStatus(419)->assertSee('Форма устарела');
        $this->get('/__throttled')->assertStatus(429)->assertSee('Слишком много попыток');
    }

    public function test_maintenance_page_is_branded(): void
    {
        Route::get('/__down', fn () => abort(503));

        $this->get('/__down')->assertStatus(503)->assertSee('Идут технические работы');
    }
}
