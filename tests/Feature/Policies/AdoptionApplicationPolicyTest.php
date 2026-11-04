<?php

namespace Tests\Feature\Policies;

use App\Enums\ApplicationStatus;
use App\Models\AdoptionApplication;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

class AdoptionApplicationPolicyTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_application_is_visible_to_author_shelter_and_admin(): void
    {
        $application = AdoptionApplication::factory()->create();

        $this->assertTrue(Gate::forUser($application->user)->allows('view', $application));
        $this->assertTrue(Gate::forUser($this->staffOf($application->pet->shelter))->allows('view', $application));
        $this->assertTrue(Gate::forUser($this->admin())->allows('view', $application));
        $this->assertFalse(Gate::forUser($this->volunteer())->allows('view', $application));
        $this->assertFalse(Gate::forUser($this->staffOf(Shelter::factory()->create()))->allows('view', $application));
    }

    public function test_only_shelter_processes_applications(): void
    {
        $application = AdoptionApplication::factory()->create();

        $this->assertTrue(Gate::forUser($this->staffOf($application->pet->shelter))->allows('process', $application));
        $this->assertFalse(Gate::forUser($this->admin())->allows('process', $application), 'админ — только просмотр');
        $this->assertFalse(Gate::forUser($application->user)->allows('process', $application));
    }

    public function test_author_withdraws_until_decision(): void
    {
        foreach ([ApplicationStatus::New, ApplicationStatus::InReview] as $status) {
            $application = AdoptionApplication::factory()->status($status)->create();
            $this->assertTrue(Gate::forUser($application->user)->allows('withdraw', $application), $status->value);
            $this->assertFalse(Gate::forUser($this->volunteer())->allows('withdraw', $application), $status->value);
        }

        foreach ([ApplicationStatus::Approved, ApplicationStatus::Rejected, ApplicationStatus::Completed, ApplicationStatus::Withdrawn] as $status) {
            $application = AdoptionApplication::factory()->status($status)->create();
            $this->assertFalse(Gate::forUser($application->user)->allows('withdraw', $application), $status->value);
        }
    }
}
