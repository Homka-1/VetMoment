<?php

namespace Tests\Feature\Shelters;

use App\Enums\ShelterStatus;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Решения администратора по приютам: pending → approved / rejected; approved ⇄ blocked.
 */
class ShelterModerationTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private function url(Shelter $shelter, string $action): string
    {
        return route("admin.shelters.{$action}", $shelter);
    }

    public function test_admin_approves_pending_shelter(): void
    {
        $shelter = Shelter::factory()->pending()->create();

        $this->actingAs($this->admin())->post($this->url($shelter, 'approve'))->assertRedirect();

        $shelter->refresh();
        $this->assertSame(ShelterStatus::Approved, $shelter->status);
        $this->assertNotNull($shelter->approved_at);
        $this->assertTrue(Shelter::approved()->whereKey($shelter->id)->exists());
    }

    public function test_admin_rejects_with_reason(): void
    {
        $shelter = Shelter::factory()->pending()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post($this->url($shelter, 'reject'), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)
            ->post($this->url($shelter, 'reject'), ['reason' => 'Не подтверждён адрес приюта.'])
            ->assertRedirect();

        $shelter->refresh();
        $this->assertSame(ShelterStatus::Rejected, $shelter->status);
        $this->assertSame('Не подтверждён адрес приюта.', $shelter->moderation_comment);
    }

    public function test_admin_blocks_and_unblocks_approved_shelter(): void
    {
        $shelter = Shelter::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post($this->url($shelter, 'block'), ['reason' => 'Жалобы волонтёров на условия.'])
            ->assertRedirect();
        $this->assertSame(ShelterStatus::Blocked, $shelter->fresh()->status);
        $this->assertFalse(Shelter::approved()->whereKey($shelter->id)->exists());

        $this->actingAs($admin)->post($this->url($shelter, 'unblock'))->assertRedirect();
        $this->assertSame(ShelterStatus::Approved, $shelter->fresh()->status);
        $this->assertNull($shelter->fresh()->moderation_comment);
    }

    public function test_invalid_transitions_are_forbidden(): void
    {
        $admin = $this->admin();
        $approved = Shelter::factory()->create();
        $pending = Shelter::factory()->pending()->create();
        $reason = ['reason' => 'Причина для проверки перехода.'];

        $this->actingAs($admin)->post($this->url($approved, 'approve'))->assertForbidden();
        $this->actingAs($admin)->post($this->url($approved, 'reject'), $reason)->assertForbidden();
        $this->actingAs($admin)->post($this->url($pending, 'block'), $reason)->assertForbidden();
        $this->actingAs($admin)->post($this->url($pending, 'unblock'))->assertForbidden();
    }

    public function test_only_admin_can_moderate(): void
    {
        $shelter = Shelter::factory()->pending()->create();
        $ownStaff = $this->staffOf($shelter);

        $this->post($this->url($shelter, 'approve'))->assertRedirect('/login');
        $this->actingAs($this->volunteer())->post($this->url($shelter, 'approve'))->assertForbidden();
        $this->actingAs($ownStaff)->post($this->url($shelter, 'approve'))->assertForbidden();

        $this->assertSame(ShelterStatus::Pending, $shelter->fresh()->status);
    }
}
