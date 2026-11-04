<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_header_navigation_footer_and_icon_sprite(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('У каждого здесь есть имя')
            ->assertSee('aria-label="Основные разделы"', escape: false)
            ->assertSeeInOrder(['Питомцы', 'Сборы', 'Выгул', 'Приюты', 'Как помочь'])
            ->assertSee('id="i-paw"', escape: false)
            ->assertSee('id="mobile-menu"', escape: false)
            ->assertSee('Войти')
            ->assertSee('Политика обработки персональных данных');
    }

    public function test_guest_does_not_see_profile_menu(): void
    {
        $this->get('/')->assertDontSee('Личный кабинет');
    }

    public function test_signed_in_user_sees_profile_menu_and_logout(): void
    {
        $user = User::factory()->create(['name' => 'Айгерим Сейткали']);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Айгерим Сейткали')
            ->assertSee('Личный кабинет')
            ->assertSee('action="'.route('logout').'"', escape: false)
            ->assertSee('aria-label="Уведомления"', escape: false);
    }

    public function test_flash_status_is_shown_as_toast(): void
    {
        $this->withSession(['status' => 'Изменения сохранены'])->get('/')
            ->assertOk()
            ->assertSee('data-toast', escape: false)
            ->assertSee('Изменения сохранены');
    }

    public function test_cabinet_uses_sidebar_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/cabinet')
            ->assertOk()
            ->assertSee('aria-label="Разделы кабинета"', escape: false)
            ->assertSee('aria-current="page"', escape: false);
    }

    public function test_validation_error_is_rendered_next_to_field(): void
    {
        $this->from('/login')->post('/login', ['identifier' => 'nobody', 'password' => 'wrong-password'])
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertSee('id="f-identifier-error"', escape: false)
            ->assertSee('aria-invalid="true"', escape: false);
    }
}
