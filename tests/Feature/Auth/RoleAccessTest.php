<?php

namespace Tests\Feature\Auth;

use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Доступ к кабинетам по ролям (middleware `auth` и `role:`).
 */
class RoleAccessTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/cabinet', '/shelter', '/admin'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_volunteer_sees_only_own_cabinet(): void
    {
        $this->actingAs($this->volunteer());

        $this->get('/cabinet')->assertOk()->assertSee('Кабинет волонтёра');
        $this->get('/shelter')->assertForbidden();
        $this->get('/admin')->assertForbidden();
    }

    public function test_shelter_staff_has_volunteer_and_shelter_cabinets(): void
    {
        $shelter = Shelter::factory()->create(['name' => 'Приют «Дом лап»']);
        $this->actingAs($this->staffOf($shelter));

        $this->get('/cabinet')->assertOk();
        $this->get('/shelter')->assertOk()->assertSee('Дом лап');
        $this->get('/admin')->assertForbidden();
    }

    public function test_admin_has_admin_panel_but_not_shelter_cabinet(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin')->assertOk()->assertSee('Админка');
        $this->get('/cabinet')->assertOk();
        $this->get('/shelter')->assertForbidden();
    }

    public function test_user_blocked_during_session_is_logged_out(): void
    {
        $user = $this->volunteer();
        $this->actingAs($user)->get('/cabinet')->assertOk();

        $user->update(['is_blocked' => true]);

        $this->get('/cabinet')->assertRedirect(route('login'))->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }
}
