<?php

namespace Tests\Feature\ShelterCabinet;

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PetStatus;
use App\Enums\ShelterMemberRole;
use App\Models\AdoptionApplication;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Support\Money;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Сводка и профиль кабинета приюта.
 */
class ShelterDashboardTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    private Shelter $shelter;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 06:00:00'); // 11:00 в Алматы
        $this->shelter = Shelter::factory()->create(['name' => 'Дом лап', 'city' => 'Алматы']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_counts_only_own_data(): void
    {
        Pet::factory()->count(2)->for($this->shelter)->status(PetStatus::LookingForHome)->create();
        Pet::factory()->for($this->shelter)->status(PetStatus::OnTreatment)->create();
        Pet::factory()->status(PetStatus::LookingForHome)->create(); // чужой

        $mine = Pet::factory()->for($this->shelter)->create(['name' => 'Бублик']);
        AdoptionApplication::factory()->for($mine)->create(['status' => ApplicationStatus::New, 'contact_name' => 'Айгерим Тестова']);
        AdoptionApplication::factory()->create(['status' => ApplicationStatus::New, 'contact_name' => 'Чужая Заявка']);

        $campaign = DonationCampaign::factory()->for($this->shelter)->status(CampaignStatus::Active)->create();
        Donation::factory()->forCampaign($campaign)->create(['amount' => 500_000]);
        Donation::factory()->forCampaign($campaign)->create(['amount' => 300_000, 'paid_at' => now()->subMonths(2)]);
        Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Pending)->create(['amount' => 100_000_000]);
        Donation::factory()->create(['amount' => 700_000]); // чужой приют

        $this->actingAs($this->staffOf($this->shelter))->get(route('shelter.dashboard'))
            ->assertOk()
            ->assertSee('Сводка')
            ->assertSee('Ищут дом')
            ->assertSee('и 1 на лечении')
            ->assertSee('Айгерим Тестова')
            ->assertDontSee('Чужая Заявка')
            ->assertSee(Money::format(500_000))
            ->assertDontSee(Money::format(800_000))
            ->assertDontSee(Money::format(100_000_000));
    }

    public function test_dashboard_lists_todays_bookings_with_actions(): void
    {
        $dog = Pet::factory()->dog()->for($this->shelter)->create(['name' => 'Граф']);
        $slot = WalkSlot::factory()->for($this->shelter)->startingAt(Carbon::parse('2026-11-10 17:00', 'Asia/Almaty'))->create();
        $tomorrow = WalkSlot::factory()->for($this->shelter)->startingAt(Carbon::parse('2026-11-11 17:00', 'Asia/Almaty'))->create();

        $booking = WalkBooking::factory()->for($slot, 'slot')->for($dog)->create(['status' => BookingStatus::Pending]);
        WalkBooking::factory()->for($tomorrow, 'slot')->create(['status' => BookingStatus::Pending]);
        $slot->recalculateBookedCount();

        $this->actingAs($this->staffOf($this->shelter))->get(route('shelter.dashboard'))
            ->assertOk()
            ->assertSee('Записи на сегодня')
            ->assertSee('17:00–18:00')
            ->assertSee('Граф')
            ->assertSee('Подтвердить')
            ->assertSee($booking->user->name)
            ->assertSee('1 ждут подтверждения', false);
    }

    public function test_dashboard_without_data_suggests_next_steps(): void
    {
        $this->actingAs($this->staffOf($this->shelter))->get(route('shelter.dashboard'))
            ->assertOk()
            ->assertSee('На сегодня записей нет')
            ->assertSee('Новых заявок нет');
    }

    public function test_pending_shelter_sees_moderation_banner(): void
    {
        $pending = Shelter::factory()->pending()->create();

        $this->actingAs($this->staffOf($pending))->get(route('shelter.dashboard'))
            ->assertOk()->assertSee('Приют на проверке');
    }

    public function test_profile_is_saved_by_owner(): void
    {
        $owner = $this->staffOf($this->shelter);

        $this->actingAs($owner)->get(route('shelter.profile'))->assertOk()->assertSee('Правила выгула');

        $this->patch(route('shelter.profile.update'), $this->payload([
            'name' => 'Дом лап 2', 'phone' => '8 727 300 12 40', 'walk_rules' => "- С 18 лет\n- Берите паспорт", 'website' => '',
        ]))->assertRedirect(route('shelter.profile'));

        $this->shelter->refresh();
        $this->assertSame('Дом лап 2', $this->shelter->name);
        $this->assertSame('+77273001240', $this->shelter->phone);
        $this->assertSame("- С 18 лет\n- Берите паспорт", $this->shelter->walk_rules);
        $this->assertNull($this->shelter->website);
    }

    public function test_profile_does_not_change_status_or_bin(): void
    {
        $this->shelter->update(['bin_iin' => '120440012345']);
        $owner = $this->staffOf($this->shelter);

        $this->actingAs($owner)->patch(route('shelter.profile.update'), $this->payload([
            'status' => 'blocked', 'bin_iin' => '000000000000', 'moderation_comment' => 'x',
        ]));

        $this->shelter->refresh();
        $this->assertTrue($this->shelter->isApproved());
        $this->assertSame('120440012345', $this->shelter->bin_iin);
        $this->assertNull($this->shelter->moderation_comment);
    }

    public function test_profile_validates_fields(): void
    {
        $this->actingAs($this->staffOf($this->shelter))->patch(route('shelter.profile.update'), $this->payload([
            'name' => '', 'phone' => '123', 'description' => 'Коротко', 'website' => 'не ссылка',
        ]))->assertSessionHasErrors(['name', 'phone', 'description', 'website']);
    }

    public function test_non_owner_staff_cannot_open_or_change_profile(): void
    {
        $staff = $this->staffOf($this->shelter, ShelterMemberRole::Staff);

        $this->actingAs($staff)->get(route('shelter.profile'))->assertForbidden();
        $this->patch(route('shelter.profile.update'), $this->payload(['name' => 'Взлом']))->assertForbidden();
        $this->assertSame('Дом лап', $this->shelter->fresh()->name);

        $this->assertNotContains('Профиль приюта', collect(Navigation::cabinet($staff))->pluck('label')->all());
    }

    public function test_volunteer_cannot_open_shelter_profile(): void
    {
        $this->actingAs($this->volunteer())->get(route('shelter.profile'))->assertForbidden();
    }

    public function test_logo_is_replaced_and_removed(): void
    {
        Storage::fake('public');
        $owner = $this->staffOf($this->shelter);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        $this->actingAs($owner)->patch(route('shelter.profile.update'), $this->payload([
            'logo' => UploadedFile::fake()->createWithContent('logo.png', $png),
        ]))->assertSessionHasNoErrors();

        $first = $this->shelter->fresh()->logo_path;
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);

        $this->patch(route('shelter.profile.update'), $this->payload([
            'logo' => UploadedFile::fake()->createWithContent('new.png', $png),
        ]));
        $second = $this->shelter->fresh()->logo_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->patch(route('shelter.profile.update'), $this->payload(['remove_logo' => '1']));
        $this->assertNull($this->shelter->fresh()->logo_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_logo_must_be_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->staffOf($this->shelter))->patch(route('shelter.profile.update'), $this->payload([
            'logo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('logo');
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return [
            'name' => 'Дом лап',
            'city' => 'Алматы',
            'address' => 'ул. Жандосова, 186',
            'phone' => '+7 701 234 56 78',
            'email' => 'info@domlap.kz',
            'description' => str_repeat('Мы работаем с 2017 года и принимаем собак и кошек. ', 2),
            'walk_rules' => null,
            'website' => null,
            ...$override,
        ];
    }
}
