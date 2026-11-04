<?php

namespace Tests\Feature\Shelters;

use App\Enums\Role;
use App\Enums\ShelterMemberRole;
use App\Enums\ShelterStatus;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * «Стать приютом-партнёром»: заявка → модерация (docs/product/user-flows.md, 6).
 */
class ShelterApplicationTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Приют «Лапа помощи»',
            'city' => 'Павлодар',
            'address' => 'Павлодар, ул. Торайгырова, 12',
            'phone' => '+7 (718) 255-12-34',
            'email' => 'Help@Lapa.kz',
            'description' => str_repeat('Спасаем собак и кошек с улиц Павлодара, лечим и ищем им дом. ', 2),
            'website' => 'https://instagram.com/lapa_pomoshi',
            'bin_iin' => '170 540 012 341',
            'agree' => '1',
            ...$overrides,
        ];
    }

    /** Настоящий PNG 1×1 — без зависимости тестов от расширения GD. */
    private function pngLogo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('logo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/cabinet/shelter-request')->assertRedirect('/login');
        $this->post('/cabinet/shelter-request', $this->payload())->assertRedirect('/login');
    }

    public function test_volunteer_sees_the_form(): void
    {
        $this->actingAs($this->volunteer())->get('/cabinet/shelter-request')
            ->assertOk()
            ->assertSee('Стать приютом-партнёром');
    }

    public function test_volunteer_submits_application_and_becomes_pending_owner(): void
    {
        Storage::fake('public');
        $user = $this->volunteer();

        $this->actingAs($user)
            ->post('/cabinet/shelter-request', $this->payload(['logo' => $this->pngLogo()]))
            ->assertRedirect(route('shelter.dashboard'));

        $shelter = Shelter::firstWhere('name', 'Приют «Лапа помощи»');
        $this->assertSame(ShelterStatus::Pending, $shelter->status);
        $this->assertSame('+77182551234', $shelter->phone);
        $this->assertSame('help@lapa.kz', $shelter->email);
        $this->assertSame('170540012341', $shelter->bin_iin);
        Storage::disk('public')->assertExists($shelter->logo_path);

        $user->refresh();
        $this->assertSame(Role::ShelterStaff, $user->role);
        $this->assertTrue($user->shelter()->is($shelter));
        $this->assertSame(ShelterMemberRole::Owner, $user->shelter()->pivot->role);

        // Кабинет приюта сразу доступен, в режиме модерации.
        $this->get('/shelter')->assertOk()->assertSee('На модерации');
    }

    public function test_pending_shelter_is_not_public(): void
    {
        $this->actingAs($this->volunteer())->post('/cabinet/shelter-request', $this->payload());

        $this->assertSame(0, Shelter::approved()->count());
    }

    public function test_fields_are_validated(): void
    {
        $this->actingAs($this->volunteer())
            ->post('/cabinet/shelter-request', $this->payload([
                'description' => 'Коротко',
                'phone' => '+7 495 123 45 67',
                'bin_iin' => '170540012342',
                'website' => 'not a url',
                'agree' => null,
            ]))
            ->assertSessionHasErrors(['description', 'phone', 'bin_iin', 'website', 'agree']);

        $this->assertSame(0, Shelter::count());
    }

    public function test_logo_must_be_a_small_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->volunteer())
            ->post('/cabinet/shelter-request', $this->payload([
                'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('logo');

        $this->actingAs($this->volunteer())
            ->post('/cabinet/shelter-request', $this->payload([
                'logo' => $this->pngLogo()->size(3000),
            ]))
            ->assertSessionHasErrors('logo');
    }

    public function test_staff_of_existing_shelter_cannot_apply_again(): void
    {
        $staff = $this->staffOf(Shelter::factory()->create());

        $this->actingAs($staff)->get('/cabinet/shelter-request')->assertRedirect(route('shelter.dashboard'));
        $this->actingAs($staff)->post('/cabinet/shelter-request', $this->payload())->assertForbidden();
        $this->assertSame(1, Shelter::count());
    }

    public function test_admin_cannot_apply(): void
    {
        $this->actingAs($this->admin())->get('/cabinet/shelter-request')->assertForbidden();
        $this->actingAs($this->admin())->post('/cabinet/shelter-request', $this->payload())->assertForbidden();
    }

    public function test_owner_resubmits_rejected_application(): void
    {
        $shelter = Shelter::factory()->rejected()->create();
        $owner = $this->staffOf($shelter);

        $this->actingAs($owner)->get('/cabinet/shelter-request')
            ->assertOk()
            ->assertSee('Не удалось подтвердить контакты приюта.');

        $this->actingAs($owner)
            ->post('/cabinet/shelter-request', $this->payload(['name' => 'Приют «Лапа помощи» (исправлено)']))
            ->assertRedirect(route('shelter.dashboard'));

        $shelter->refresh();
        $this->assertSame(ShelterStatus::Pending, $shelter->status);
        $this->assertNull($shelter->moderation_comment);
        $this->assertSame('Приют «Лапа помощи» (исправлено)', $shelter->name);
        $this->assertSame(1, Shelter::count());
    }

    public function test_regular_staff_cannot_resubmit_for_owner(): void
    {
        $shelter = Shelter::factory()->rejected()->create();
        $staff = $this->staffOf($shelter, ShelterMemberRole::Staff);

        $this->actingAs($staff)->post('/cabinet/shelter-request', $this->payload())->assertForbidden();
        $this->assertSame(ShelterStatus::Rejected, $shelter->fresh()->status);
    }
}
