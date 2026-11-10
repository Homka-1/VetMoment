<?php

namespace Tests\Feature\ShelterCabinet;

use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Enums\WalkSlotStatus;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Models\WalkTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Слоты и записи на выгул в кабинете приюта: разовый слот, шаблон недели, изменение и отмена слота,
 * подтверждение / отказ / отметки после прогулки.
 */
class WalkSlotsTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private Shelter $shelter;

    private User $staff;

    private Pet $dog;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 06:00:00'); // вторник, 11:00 в Алматы
        $this->shelter = Shelter::factory()->create();
        $this->staff = $this->staffOf($this->shelter);
        $this->dog = Pet::factory()->dog()->for($this->shelter)->create(['name' => 'Бублик']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function slotData(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-11-13', 'start_time' => '16:00', 'end_time' => '17:00',
            'capacity' => 6, 'meeting_point' => 'У ворот', 'pets' => [$this->dog->id],
        ], $overrides);
    }

    public function test_week_calendar_shows_own_slots(): void
    {
        WalkSlot::factory()->for($this->shelter)->startingAt(Carbon::parse('2026-11-12 17:00', 'Asia/Almaty'))->create(['capacity' => 6]);
        WalkSlot::factory()->startingAt(Carbon::parse('2026-11-12 12:00', 'Asia/Almaty'))->create(); // чужой

        $this->actingAs($this->staff)->get('/shelter/slots')->assertOk()
            ->assertSee('9–15 ноября')->assertSee('17:00')->assertSee('записано 0/6')->assertDontSee('12:00')
            ->assertSee('Новый разовый слот');
    }

    public function test_creates_one_off_slot_in_local_time(): void
    {
        $this->actingAs($this->staff)->post('/shelter/slots', $this->slotData())
            ->assertRedirect('/shelter/slots?week=2026-11-09')->assertSessionHas('status', 'Слот создан');

        $slot = WalkSlot::sole();
        $this->assertSame('2026-11-13 11:00:00', $slot->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(60, $slot->durationMinutes());
        $this->assertSame([$this->dog->id], $slot->pets->pluck('id')->all());
    }

    public function test_slot_validation(): void
    {
        $this->actingAs($this->staff);
        $this->post('/shelter/slots', $this->slotData(['end_time' => '16:15']))->assertSessionHasErrors('end_time');
        $this->post('/shelter/slots', $this->slotData(['start_time' => '16:10']))->assertSessionHasErrors('start_time');
        $this->post('/shelter/slots', $this->slotData(['date' => '2026-11-09']))->assertSessionHasErrors('date');
        $this->post('/shelter/slots', $this->slotData(['date' => '2027-02-01']))->assertSessionHasErrors('date');
        $this->post('/shelter/slots', $this->slotData(['capacity' => 21]))->assertSessionHasErrors('capacity');
        $this->post('/shelter/slots', $this->slotData(['pets' => [Pet::factory()->dog()->create()->id]]))->assertSessionHasErrors('pets.0');
        $this->assertSame(0, WalkSlot::count());

        $this->post('/shelter/slots', $this->slotData());
        $this->post('/shelter/slots', $this->slotData())->assertSessionHasErrors('start_time'); // уже есть слот на это время
        $this->assertSame(1, WalkSlot::count());
    }

    public function test_pending_shelter_cannot_create_slots(): void
    {
        $shelter = Shelter::factory()->pending()->create();
        $staff = $this->staffOf($shelter);

        $this->actingAs($staff)->get('/shelter/slots')->assertOk()->assertSee('Приют на проверке')->assertDontSee('Новый разовый слот');
        $this->post('/shelter/slots', $this->slotData(['pets' => []]))->assertForbidden();
    }

    public function test_template_creates_slots_and_skips_existing(): void
    {
        WalkSlot::factory()->for($this->shelter)->startingAt(Carbon::parse('2026-11-14 10:00', 'Asia/Almaty'))->create();

        $this->actingAs($this->staff)->post('/shelter/slots/templates', [
            'weekdays' => [6, 7], 'start_time' => '10:00', 'duration_minutes' => 60, 'capacity' => 4,
            'valid_from' => '2026-11-10', 'valid_until' => '2026-11-23', 'pets' => [$this->dog->id],
        ])->assertRedirect()->assertSessionHas('status', "Шаблон сохранён: 3\u{00A0}слота создано, 1\u{00A0}пропущен — на это время слот уже был");

        $template = WalkTemplate::sole();
        $this->assertSame([6, 7], $template->weekdayList());
        $this->assertSame(3, $template->slots()->count());
        $this->assertSame(4, WalkSlot::count());
        $this->assertTrue($template->slots()->first()->pets->contains($this->dog));
    }

    public function test_template_validation(): void
    {
        $this->actingAs($this->staff)->post('/shelter/slots/templates', [
            'weekdays' => [], 'start_time' => '10:00', 'duration_minutes' => 20, 'capacity' => 4,
            'valid_from' => '2026-11-10', 'valid_until' => '2027-03-01',
        ])->assertSessionHasErrors(['weekdays', 'duration_minutes', 'valid_until']);
    }

    public function test_deleting_template_keeps_slots_with_bookings(): void
    {
        $template = WalkTemplate::factory()->for($this->shelter)->create();
        $free = WalkSlot::factory()->for($this->shelter)->create(['template_id' => $template->id]);
        $booked = WalkSlot::factory()->for($this->shelter)->create(['template_id' => $template->id]);
        WalkBooking::factory()->create(['walk_slot_id' => $booked->id]);

        $this->actingAs($this->volunteer())->delete(route('shelter.slot-templates.destroy', $template))->assertForbidden();
        $this->actingAs($this->staff)->delete(route('shelter.slot-templates.destroy', $template))->assertRedirect();

        $this->assertModelMissing($template);
        $this->assertModelMissing($free);
        $this->assertModelExists($booked);
    }

    public function test_slot_with_bookings_keeps_time_and_capacity(): void
    {
        $slot = WalkSlot::factory()->for($this->shelter)->startingAt(Carbon::parse('2026-11-13 16:00', 'Asia/Almaty'))->create(['capacity' => 6]);
        WalkBooking::factory()->count(2)->create(['walk_slot_id' => $slot->id]);
        $slot->recalculateBookedCount();

        $this->actingAs($this->staff)->patch(route('shelter.slots.update', $slot), $this->slotData(['start_time' => '17:00', 'end_time' => '18:00']))
            ->assertSessionHasErrors('start_time');
        $this->patch(route('shelter.slots.update', $slot), $this->slotData(['capacity' => 1]))->assertSessionHasErrors('capacity');

        $this->patch(route('shelter.slots.update', $slot), $this->slotData(['capacity' => 8, 'meeting_point' => 'Парковка']))
            ->assertSessionHas('status', 'Слот сохранён');
        $this->assertSame(8, $slot->fresh()->capacity);
        $this->assertSame('Парковка', $slot->fresh()->meeting_point);
    }

    public function test_cancelling_slot_cancels_bookings_with_reason(): void
    {
        $slot = WalkSlot::factory()->for($this->shelter)->create();
        $booking = WalkBooking::factory()->create(['walk_slot_id' => $slot->id]);

        $this->actingAs($this->staff)->post(route('shelter.slots.cancel', $slot), [])->assertSessionHasErrors('reason');
        $this->post(route('shelter.slots.cancel', $slot), ['reason' => 'Карантин'])
            ->assertSessionHas('status', "Слот отменён, 1\u{00A0}запись отменена");

        $this->assertSame(WalkSlotStatus::Cancelled, $slot->fresh()->status);
        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame(CancelledBy::Shelter, $booking->cancelled_by);
        $this->assertSame('Карантин', $booking->shelter_comment);
    }

    public function test_foreign_slot_is_forbidden(): void
    {
        $foreign = WalkSlot::factory()->create();

        $this->actingAs($this->staff)->get(route('shelter.slots.show', $foreign))->assertForbidden();
        $this->patch(route('shelter.slots.update', $foreign), $this->slotData())->assertForbidden();
        $this->post(route('shelter.slots.cancel', $foreign), ['reason' => 'x'])->assertForbidden();
    }

    public function test_bookings_list_and_decisions(): void
    {
        $slot = WalkSlot::factory()->for($this->shelter)->create();
        $volunteer = $this->volunteer(['name' => 'Анна Ли']);
        $booking = WalkBooking::factory()->for($volunteer)->create(['walk_slot_id' => $slot->id, 'comment' => 'Первый раз']);
        WalkBooking::factory()->create(); // чужой приют

        $this->actingAs($this->staff)->get('/shelter/bookings')->assertOk()
            ->assertSee('Анна Ли')->assertSee('Первый раз')->assertSee('Подтвердить');

        $this->patch(route('shelter.bookings.update', $booking), ['status' => 'rejected'])->assertSessionHasErrors('shelter_comment');
        $this->patch(route('shelter.bookings.update', $booking), ['status' => 'completed'])->assertSessionHasErrors('status');

        $this->patch(route('shelter.bookings.update', $booking), ['status' => 'confirmed'])
            ->assertSessionHas('status', 'Запись подтверждена');
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        $this->get('/shelter/bookings?tab=upcoming')->assertSee('Анна Ли');

        Carbon::setTestNow(now()->addDays(10));
        $this->get('/shelter/bookings?tab=to_mark')->assertSee('Анна Ли')->assertSee('Не пришёл');
        $this->patch(route('shelter.bookings.update', $booking), ['status' => 'completed'])
            ->assertSessionHas('status', 'Отмечено: выгул состоялся');
        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_frequent_no_show_is_flagged(): void
    {
        $volunteer = $this->volunteer(['name' => 'Тимур']);
        foreach ([3, 5, 7] as $days) {
            WalkBooking::factory()->for($volunteer)->status(BookingStatus::NoShow)
                ->create(['walk_slot_id' => WalkSlot::factory()->startingAt(now()->subDays($days))->create()->id]);
        }
        WalkBooking::factory()->for($volunteer)->create(['walk_slot_id' => WalkSlot::factory()->for($this->shelter)->create()->id]);

        $this->actingAs($this->staff)->get('/shelter/bookings')->assertSee('Часто не приходит');
    }

    public function test_other_shelter_cannot_decide(): void
    {
        $booking = WalkBooking::factory()->create();

        $this->actingAs($this->staff)->patch(route('shelter.bookings.update', $booking), ['status' => 'confirmed'])->assertForbidden();
    }
}
