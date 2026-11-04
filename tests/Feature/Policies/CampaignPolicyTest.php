<?php

namespace Tests\Feature\Policies;

use App\Enums\CampaignStatus;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Матрица прав на сборы — docs/product/roles.md, 2.3.
 */
class CampaignPolicyTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_guests_view_and_donate_to_active_campaign(): void
    {
        $campaign = DonationCampaign::factory()->create();

        $this->assertTrue(Gate::forUser(null)->allows('view', $campaign));
        $this->assertTrue(Gate::forUser(null)->allows('donate', $campaign));
    }

    public function test_finished_campaign_is_visible_but_closed_for_donations(): void
    {
        foreach ([CampaignStatus::Completed, CampaignStatus::Closed] as $status) {
            $campaign = DonationCampaign::factory()->status($status)->create();

            $this->assertTrue(Gate::forUser(null)->allows('view', $campaign), $status->value);
            $this->assertFalse(Gate::forUser(null)->allows('donate', $campaign), $status->value);
        }
    }

    public function test_draft_and_hidden_campaigns_are_private(): void
    {
        $shelter = Shelter::factory()->create();
        $draft = DonationCampaign::factory()->for($shelter)->status(CampaignStatus::Draft)->create();
        $hidden = DonationCampaign::factory()->for($shelter)->create(['is_hidden' => true]);

        foreach ([$draft, $hidden] as $campaign) {
            $this->assertFalse(Gate::forUser(null)->allows('view', $campaign));
            $this->assertFalse(Gate::forUser(null)->allows('donate', $campaign));
            $this->assertTrue(Gate::forUser($this->staffOf($shelter))->allows('view', $campaign));
            $this->assertTrue(Gate::forUser($this->admin())->allows('view', $campaign));
        }
    }

    public function test_campaign_of_blocked_shelter_is_hidden(): void
    {
        $campaign = DonationCampaign::factory()->for(Shelter::factory()->blocked())->create();

        $this->assertFalse(Gate::forUser(null)->allows('view', $campaign));
        $this->assertFalse(Gate::forUser(null)->allows('donate', $campaign));
    }

    public function test_management_is_limited_to_own_shelter(): void
    {
        $shelter = Shelter::factory()->create();
        $campaign = DonationCampaign::factory()->for($shelter)->create();
        $own = $this->staffOf($shelter);
        $other = $this->staffOf(Shelter::factory()->create());

        foreach (['update', 'delete', 'publish', 'manageReports', 'viewDonations'] as $ability) {
            $this->assertTrue(Gate::forUser($own)->allows($ability, $campaign), $ability);
            $this->assertTrue(Gate::forUser($this->admin())->allows($ability, $campaign), $ability);
            $this->assertFalse(Gate::forUser($other)->allows($ability, $campaign), $ability);
            $this->assertFalse(Gate::forUser($this->volunteer())->allows($ability, $campaign), $ability);
        }
    }

    public function test_pending_shelter_prepares_drafts_but_cannot_launch(): void
    {
        $shelter = Shelter::factory()->pending()->create();
        $draft = DonationCampaign::factory()->for($shelter)->status(CampaignStatus::Draft)->create();
        $own = $this->staffOf($shelter);

        $this->assertTrue(Gate::forUser($own)->allows('create', DonationCampaign::class));
        $this->assertTrue(Gate::forUser($own)->allows('update', $draft));
        $this->assertFalse(Gate::forUser($own)->allows('publish', $draft));
    }

    public function test_volunteer_cannot_create_campaigns_and_only_admin_hides(): void
    {
        $campaign = DonationCampaign::factory()->create();

        $this->assertFalse(Gate::forUser($this->volunteer())->allows('create', DonationCampaign::class));
        $this->assertTrue(Gate::forUser($this->admin())->allows('hide', $campaign));
        $this->assertFalse(Gate::forUser($this->staffOf($campaign->shelter))->allows('hide', $campaign));
    }
}
