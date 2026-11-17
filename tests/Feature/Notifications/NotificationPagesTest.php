<?php

namespace Tests\Feature\Notifications;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Notifications\WalkBookingDecided;
use App\Notifications\WalkReminder;
use App\Services\WalkBookingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Этап 12: доставка (database сразу + письмо), страница /notifications, счётчики,
 * команды расписания (напоминания, неподтверждённые записи).
 */
class NotificationPagesTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private Shelter $shelter;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 06:00:00');
        $this->shelter = Shelter::factory()->create(['name' => 'Дом лап']);
        $this->user = $this->volunteer(['name' => 'Марат Тестовый']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function booking(BookingStatus $status, string $in = '+20 hours', array $attributes = []): WalkBooking
    {
        $slot = WalkSlot::factory()->for($this->shelter)->startingAt(now()->modify($in))->create();

        return WalkBooking::factory()->status($status)->create(['walk_slot_id' => $slot->id, 'user_id' => $this->user->id, ...$attributes]);
    }

    private function notifyConfirmed(): WalkBooking
    {
        $booking = $this->booking(BookingStatus::Confirmed);
        $this->user->notify(new WalkBookingDecided($booking));

        return $booking;
    }

    public function test_database_record_is_written_immediately_with_short_type(): void
    {
        $booking = $this->notifyConfirmed();

        $notification = $this->user->notifications()->sole();
        $this->assertSame('walk.confirmed', $notification->type);
        $this->assertSame('Прогулка подтверждена', $notification->data['title']);
        $this->assertSame(route('walks.bookings.show', $booking), $notification->data['url']);
        $this->assertSame(1, $this->user->unreadNotificationsCount());
    }

    public function test_mail_is_in_russian_with_action_button(): void
    {
        $booking = $this->booking(BookingStatus::Confirmed);
        $mail = (new WalkReminder($booking))->toMail($this->user);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Скоро прогулка', $mail->subject);
        $this->assertSame('Здравствуйте, Марат Тестовый!', $mail->greeting);
        $this->assertSame('Открыть запись', $mail->actionText);
        $html = (string) $mail->render();
        $this->assertStringContainsString('«Дом лап»', $html);
        $this->assertStringContainsString('С заботой о хвостатых', $html);

        // Фирменный шаблон (этап 13): логотип PNG, цвета токенов, подвал без «All rights reserved».
        $this->assertStringContainsString('images/brand/logo-email.png', $html);
        $this->assertStringContainsString('#1D2B4F', $html);
        $this->assertStringContainsString('личном кабинете', $html);
        $this->assertStringNotContainsString('Laravel', $html);
    }

    public function test_notifications_page_lists_and_marks_read_on_open(): void
    {
        $booking = $this->notifyConfirmed();
        $id = $this->user->notifications()->value('id');

        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Прогулка подтверждена')
            ->assertSee('Прочитать все')
            ->assertSee('Новое:', false);

        $this->get(route('notifications.open', $id))->assertRedirect(route('walks.bookings.show', $booking));
        $this->assertNotNull($this->user->notifications()->value('read_at'));

        $this->get(route('notifications.index', ['tab' => 'unread']))->assertOk()->assertSee('Новых уведомлений нет');
    }

    public function test_cannot_open_someone_elses_notification(): void
    {
        $this->notifyConfirmed();
        $id = $this->user->notifications()->value('id');

        $this->actingAs($this->volunteer())->get(route('notifications.open', $id))->assertNotFound();
        $this->assertNull($this->user->notifications()->value('read_at'));
    }

    public function test_read_all_and_counters_in_header_and_menu(): void
    {
        $this->notifyConfirmed();
        $this->notifyConfirmed();

        $this->actingAs($this->user)->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Уведомления: непрочитанных 2')
            ->assertSee(route('notifications.index'));

        $this->post(route('notifications.read-all'))->assertRedirect(route('notifications.index'));
        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_empty_page_invites_to_wait_for_answers(): void
    {
        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Уведомлений пока нет')
            ->assertDontSee('Прочитать все');
    }

    public function test_reminders_are_sent_once_and_only_for_confirmed_within_24_hours(): void
    {
        Notification::fake();
        $soon = $this->booking(BookingStatus::Confirmed, '+20 hours');
        $this->booking(BookingStatus::Confirmed, '+30 hours');
        $this->booking(BookingStatus::Pending, '+10 hours');

        $this->artisan('app:send-walk-reminders')->expectsOutputToContain('1')->assertSuccessful();
        $this->artisan('app:send-walk-reminders')->assertSuccessful();

        Notification::assertSentToTimes($this->user, WalkReminder::class, 1);
        $this->assertNotNull($soon->refresh()->reminder_sent_at);
    }

    public function test_unconfirmed_bookings_are_cancelled_after_start(): void
    {
        Notification::fake();
        $started = $this->booking(BookingStatus::Pending, '+1 hour');
        $future = $this->booking(BookingStatus::Pending, '+1 day');
        Carbon::setTestNow(now()->addHours(2));

        $this->artisan('app:expire-unconfirmed-bookings')->assertSuccessful();

        $started->refresh();
        $this->assertSame(BookingStatus::Cancelled, $started->status);
        $this->assertSame(CancelledBy::System, $started->cancelled_by);
        $this->assertSame(WalkBookingService::UNCONFIRMED_COMMENT, $started->shelter_comment);
        $this->assertSame(0, $started->slot->refresh()->booked_count);
        $this->assertSame(BookingStatus::Pending, $future->refresh()->status);
        Notification::assertSentTo($this->user, WalkBookingDecided::class, fn ($n) => str_contains($n->body(), 'не успел подтвердить'));
    }

    public function test_schedule_contains_background_commands(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command)->implode("\n");

        foreach (['app:expire-pending-donations', 'app:close-expired-campaigns', 'app:send-walk-reminders', 'app:expire-unconfirmed-bookings'] as $command) {
            $this->assertStringContainsString($command, $commands);
        }
    }
}
