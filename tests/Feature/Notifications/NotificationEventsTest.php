<?php

namespace Tests\Feature\Notifications;

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShelterMemberRole;
use App\Models\AdoptionApplication;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Notifications\ApplicationDecided;
use App\Notifications\ApplicationSubmitted;
use App\Notifications\CampaignGoalReached;
use App\Notifications\DonationReceived;
use App\Notifications\ShelterModerated;
use App\Notifications\WalkBookingCancelled;
use App\Notifications\WalkBookingCreated;
use App\Notifications\WalkBookingDecided;
use App\Payments\WebhookEvent;
use App\Services\AdoptionApplications;
use App\Services\DonationPayments;
use App\Services\ShelterModeration;
use App\Services\UserModeration;
use App\Services\WalkBookingService;
use App\Services\WalkSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Этап 12: какие события кому отправляют уведомления (docs/product/user-flows.md).
 */
class NotificationEventsTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private Shelter $shelter;

    private User $owner;

    private User $volunteer;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 06:00:00');
        Notification::fake();
        $this->shelter = Shelter::factory()->create();
        $this->owner = $this->staffOf($this->shelter);
        $this->volunteer = $this->volunteer();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function slot(string $in = '+1 day'): WalkSlot
    {
        return WalkSlot::factory()->for($this->shelter)->startingAt(now()->modify($in))->create();
    }

    private function booking(BookingStatus $status = BookingStatus::Pending, string $in = '+1 day'): WalkBooking
    {
        $slot = $this->slot($in);
        $booking = WalkBooking::factory()->status($status)->create(['walk_slot_id' => $slot->id, 'user_id' => $this->volunteer->id]);
        $slot->recalculateBookedCount();

        return $booking;
    }

    public function test_new_booking_notifies_shelter_staff_but_not_blocked_ones(): void
    {
        $blocked = $this->staffOf($this->shelter, ShelterMemberRole::Staff);
        $blocked->forceFill(['is_blocked' => true])->save();

        app(WalkBookingService::class)->book($this->volunteer, $this->slot(), null);

        Notification::assertSentTo($this->owner, WalkBookingCreated::class, function (WalkBookingCreated $n, array $channels) {
            return $channels === ['database', 'mail'] && str_contains($n->body(), $this->volunteer->name);
        });
        Notification::assertNotSentTo($blocked, WalkBookingCreated::class);
        Notification::assertNotSentTo($this->volunteer, WalkBookingCreated::class);
    }

    public function test_shelter_decisions_notify_volunteer(): void
    {
        $service = app(WalkBookingService::class);

        $confirmed = $this->booking();
        $service->process($confirmed, BookingStatus::Confirmed);
        Notification::assertSentTo($this->volunteer, WalkBookingDecided::class, fn ($n) => $n->kind() === 'walk.confirmed');

        $rejected = $this->booking(in: '+2 days');
        $service->process($rejected, BookingStatus::Rejected, 'Слот только для опытных');
        Notification::assertSentTo($this->volunteer, WalkBookingDecided::class,
            fn ($n) => $n->kind() === 'walk.rejected' && str_contains($n->body(), 'Слот только для опытных'));

        $service->process($confirmed->refresh(), BookingStatus::Cancelled, 'Карантин');
        Notification::assertSentTo($this->volunteer, WalkBookingDecided::class, fn ($n) => $n->kind() === 'walk.cancelled');
        Notification::assertSentToTimes($this->volunteer, WalkBookingDecided::class, 3);
    }

    public function test_marking_completed_does_not_notify(): void
    {
        $booking = $this->booking(BookingStatus::Confirmed);
        Carbon::setTestNow(now()->addDays(1)->addMinutes(10));

        app(WalkBookingService::class)->process($booking, BookingStatus::Completed);

        Notification::assertNothingSent();
    }

    public function test_volunteer_cancel_notifies_shelter(): void
    {
        app(WalkBookingService::class)->cancelByUser($this->booking());

        Notification::assertSentTo($this->owner, WalkBookingCancelled::class);
    }

    public function test_cancelled_slot_notifies_every_active_booking(): void
    {
        $slot = $this->slot();
        $other = $this->volunteer();
        foreach ([$this->volunteer, $other] as $user) {
            WalkBooking::factory()->status(BookingStatus::Confirmed)->create(['walk_slot_id' => $slot->id, 'user_id' => $user->id]);
        }

        $this->assertSame(2, app(WalkSlots::class)->cancel($slot, 'Карантин'));

        foreach ([$this->volunteer, $other] as $user) {
            Notification::assertSentTo($user, WalkBookingDecided::class,
                fn ($n) => $n->kind() === 'walk.cancelled' && str_contains($n->body(), 'Карантин'));
        }
    }

    public function test_blocking_user_notifies_volunteer_and_shelter(): void
    {
        $this->booking(BookingStatus::Confirmed);

        app(UserModeration::class)->block($this->volunteer);

        Notification::assertSentTo($this->volunteer, WalkBookingDecided::class, fn ($n) => str_contains($n->body(), 'заблокирован'));
        Notification::assertSentTo($this->owner, WalkBookingCancelled::class);
    }

    public function test_applications_notify_shelter_and_applicant(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create();
        $service = app(AdoptionApplications::class);

        $application = $service->submit($this->volunteer, $pet, [
            'type' => 'adoption', 'contact_name' => 'Марат Тестовый', 'contact_phone' => '+77011234567', 'city' => 'Алматы',
        ]);
        Notification::assertSentTo($this->owner, ApplicationSubmitted::class);

        $service->process($application, ApplicationStatus::InReview);
        Notification::assertSentTo($this->volunteer, ApplicationDecided::class, fn ($n) => $n->kind() === 'application.in_review');
    }

    public function test_adoption_closes_other_applications_with_notifications(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create();
        $winner = AdoptionApplication::factory()->status(ApplicationStatus::Approved)->create(['pet_id' => $pet->id, 'user_id' => $this->volunteer->id]);
        $other = $this->volunteer();
        AdoptionApplication::factory()->status(ApplicationStatus::New)->create(['pet_id' => $pet->id, 'user_id' => $other->id]);

        app(AdoptionApplications::class)->process($winner, ApplicationStatus::Completed);

        Notification::assertSentTo($this->volunteer, ApplicationDecided::class, fn ($n) => $n->kind() === 'application.completed');
        Notification::assertSentTo($other, ApplicationDecided::class,
            fn ($n) => $n->kind() === 'application.rejected' && str_contains($n->body(), 'Питомец нашёл дом'));
    }

    private function pay(Donation $donation, string $tx = 'tx-1'): string
    {
        return app(DonationPayments::class)->apply('fake', new WebhookEvent($donation->uuid, $tx, PaymentStatus::Succeeded, $donation->amount));
    }

    public function test_successful_payment_thanks_user_once(): void
    {
        $donation = Donation::factory()->status(PaymentStatus::Pending)
            ->create(['shelter_id' => $this->shelter->id, 'user_id' => $this->volunteer->id, 'provider_payment_id' => null]);

        $this->pay($donation);
        $this->pay($donation); // повтор webhook

        Notification::assertSentToTimes($this->volunteer, DonationReceived::class, 1);
    }

    public function test_guest_donor_gets_mail_to_entered_email(): void
    {
        $donation = Donation::factory()->status(PaymentStatus::Pending)
            ->create(['shelter_id' => $this->shelter->id, 'donor_email' => 'guest@example.kz', 'provider_payment_id' => null]);

        $this->pay($donation);

        Notification::assertSentOnDemand(DonationReceived::class, function ($n, array $channels, AnonymousNotifiable $notifiable) {
            return $channels === ['mail'] && $notifiable->routes['mail'] === 'guest@example.kz';
        });
    }

    public function test_reaching_goal_notifies_shelter_and_registered_donors(): void
    {
        $campaign = DonationCampaign::factory()->for($this->shelter)->create(['goal_amount' => 2_000 * 100]);
        $earlier = $this->volunteer();
        Donation::factory()->forCampaign($campaign)->create(['user_id' => $earlier->id, 'amount' => 1_000 * 100]);
        $campaign->recalculateTotals();

        $last = Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Pending)
            ->create(['user_id' => $this->volunteer->id, 'amount' => 1_000 * 100, 'provider_payment_id' => null]);
        $this->pay($last);

        $this->assertSame(CampaignStatus::Completed, $campaign->refresh()->status);
        Notification::assertSentTo($this->owner, CampaignGoalReached::class, fn ($n) => str_contains($n->url(), 'reports'));
        Notification::assertSentTo($earlier, CampaignGoalReached::class, fn ($n) => ! str_contains($n->url(), 'reports'));
        Notification::assertSentTo($this->volunteer, CampaignGoalReached::class);
    }

    public function test_shelter_moderation_notifies_staff(): void
    {
        $pending = Shelter::factory()->pending()->create();
        $owner = $this->staffOf($pending);

        app(ShelterModeration::class)->approve($pending);
        Notification::assertSentTo($owner, ShelterModerated::class, fn ($n) => $n->kind() === 'shelter.approved');

        app(ShelterModeration::class)->block($pending->refresh(), 'Жалобы');
        Notification::assertSentTo($owner, ShelterModerated::class, fn ($n) => $n->kind() === 'shelter.blocked' && str_contains($n->body(), 'Жалобы'));

        app(ShelterModeration::class)->unblock($pending->refresh());
        Notification::assertSentTo($owner, ShelterModerated::class, fn ($n) => $n->kind() === 'shelter.unblocked');
    }

    public function test_rejected_shelter_is_sent_to_resubmit_form(): void
    {
        $pending = Shelter::factory()->pending()->create();
        $owner = $this->staffOf($pending);

        app(ShelterModeration::class)->reject($pending, 'Нет документов');

        Notification::assertSentTo($owner, ShelterModerated::class,
            fn ($n) => $n->kind() === 'shelter.rejected' && $n->url() === route('cabinet.shelter-request'));
    }
}
