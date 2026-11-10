<?php

namespace Tests\Feature\ShelterCabinet;

use App\Enums\AttachmentType;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Models\CampaignReport;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesActors;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

/**
 * Сборы, отчёты и пожертвования в кабинете приюта.
 */
class CampaignManagementTest extends TestCase
{
    use CreatesActors, MakesImages, RefreshDatabase;

    private Shelter $shelter;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->shelter = Shelter::factory()->create();
        $this->staff = $this->staffOf($this->shelter);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Тёплые будки к зиме',
            'short_description' => 'Утеплитель и доски для 12 вольеров.',
            'description' => "Зимой бывает до −15 °C, нужно утеплить вольеры.\n\n— утеплитель — 300 000 ₸\n— доски — 200 000 ₸",
            'goal' => '500 000',
            'ends_at' => now()->addMonth()->format('Y-m-d'),
            'action' => 'publish',
            ...$overrides,
        ];
    }

    public function test_access_to_the_section(): void
    {
        $campaign = DonationCampaign::factory()->create();

        $this->get('/shelter/campaigns')->assertRedirect('/login');
        $this->actingAs($this->volunteer())->get('/shelter/campaigns')->assertForbidden();
        $this->actingAs($this->staff)->get('/shelter/campaigns')->assertOk()->assertSee('Запустите первый сбор');
        // Чужой сбор
        $this->actingAs($this->staff)->get(route('shelter.campaigns.edit', $campaign))->assertForbidden();
        $this->actingAs($this->staff)->put(route('shelter.campaigns.update', $campaign), $this->payload())->assertForbidden();
        $this->actingAs($this->staff)->post(route('shelter.campaigns.close', $campaign))->assertForbidden();
        $this->actingAs($this->staff)->get(route('shelter.campaigns.reports.index', $campaign))->assertForbidden();
    }

    public function test_create_and_launch_a_campaign_with_cover(): void
    {
        $pet = Pet::factory()->for($this->shelter)->create();

        $this->actingAs($this->staff)
            ->post(route('shelter.campaigns.store'), $this->payload(['pet_id' => $pet->id, 'cover' => $this->png('cover.png'), 'allow_overfunding' => '1']))
            ->assertRedirect(route('shelter.campaigns.index'))
            ->assertSessionHas('status', 'Сбор «Тёплые будки к зиме» запущен');

        $campaign = DonationCampaign::sole();
        $this->assertSame(CampaignStatus::Active, $campaign->status);
        $this->assertSame(500_000 * 100, $campaign->goal_amount);
        $this->assertSame($pet->id, $campaign->pet_id);
        $this->assertTrue($campaign->allow_overfunding);
        $this->assertTrue($campaign->starts_at->isToday());
        // Конец выбранного дня по Алматы
        $this->assertSame('23:59', $campaign->ends_at->setTimezone('Asia/Almaty')->format('H:i'));
        Storage::disk('public')->assertExists($campaign->cover_path);

        $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('Утеплитель');
    }

    public function test_pending_shelter_can_only_save_drafts(): void
    {
        $shelter = Shelter::factory()->pending()->create();
        $staff = $this->staffOf($shelter);

        $this->actingAs($staff)->post(route('shelter.campaigns.store'), $this->payload())->assertSessionHasErrors('action');
        $this->actingAs($staff)->post(route('shelter.campaigns.store'), $this->payload(['action' => 'draft']))
            ->assertSessionHas('status', 'Черновик «Тёплые будки к зиме» сохранён');

        $this->assertSame(CampaignStatus::Draft, DonationCampaign::sole()->status);
    }

    public function test_validation(): void
    {
        $foreignPet = Pet::factory()->create();

        $this->actingAs($this->staff)->post(route('shelter.campaigns.store'), $this->payload([
            'title' => '',
            'goal' => '1 000',
            'ends_at' => now()->addYears(2)->format('Y-m-d'),
            'pet_id' => $foreignPet->id,
            'description' => 'Коротко',
            'cover' => UploadedFile::fake()->createWithContent('cover.txt', 'не картинка'),
        ]))->assertSessionHasErrors(['title', 'goal', 'ends_at', 'pet_id', 'description', 'cover']);

        $this->actingAs($this->staff)->post(route('shelter.campaigns.store'), $this->payload(['ends_at' => now()->format('Y-m-d')]))
            ->assertSessionHasErrors('ends_at');

        $this->assertSame(0, DonationCampaign::count());
    }

    public function test_draft_is_launched_from_the_edit_form(): void
    {
        $campaign = DonationCampaign::factory()->for($this->shelter)->status(CampaignStatus::Draft)->create(['starts_at' => now()->subMonth()]);

        $this->actingAs($this->staff)->put(route('shelter.campaigns.update', $campaign), $this->payload())
            ->assertSessionHas('status', 'Сбор «Тёплые будки к зиме» запущен');

        $campaign->refresh();
        $this->assertSame(CampaignStatus::Active, $campaign->status);
        $this->assertTrue($campaign->starts_at->isToday(), 'срок отсчитывается от запуска');
    }

    public function test_changing_the_goal_updates_completion(): void
    {
        $campaign = DonationCampaign::factory()->for($this->shelter)->create(['goal_amount' => 500_000 * 100]);
        Donation::factory()->forCampaign($campaign)->create(['amount' => 300_000 * 100]);
        $campaign->recalculateTotals();

        $this->actingAs($this->staff)->put(route('shelter.campaigns.update', $campaign), $this->payload(['goal' => '300 000', 'action' => 'save']));
        $this->assertSame(CampaignStatus::Completed, $campaign->fresh()->status);
        $this->assertSame(300_000 * 100, $campaign->fresh()->raised_amount, 'собранное не меняется формой');

        $this->actingAs($this->staff)->put(route('shelter.campaigns.update', $campaign), $this->payload(['goal' => '400 000', 'action' => 'save']));
        $this->assertSame(CampaignStatus::Active, $campaign->fresh()->status);
        $this->assertNull($campaign->fresh()->completed_at);
    }

    public function test_close_and_archive(): void
    {
        $campaign = DonationCampaign::factory()->for($this->shelter)->create();
        Donation::factory()->forCampaign($campaign)->create();

        $this->actingAs($this->staff)->post(route('shelter.campaigns.close', $campaign))->assertSessionHas('status');
        $this->assertSame(CampaignStatus::Closed, $campaign->fresh()->status);
        $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('Сбор закрыт');

        $this->actingAs($this->staff)->delete(route('shelter.campaigns.destroy', $campaign))->assertRedirect(route('shelter.campaigns.index'));
        $this->assertSame(CampaignStatus::Archived, $campaign->fresh()->status);
        $this->assertSame(1, Donation::count(), 'пожертвования сохраняются');
        $this->actingAs($this->volunteer())->get(route('campaigns.show', $campaign))->assertNotFound();

        $this->actingAs($this->staff)->get('/shelter/campaigns?status=archived')->assertOk()->assertSee($campaign->title);
    }

    public function test_reports_with_photos_and_receipts(): void
    {
        $campaign = DonationCampaign::factory()->for($this->shelter)->create();

        $this->actingAs($this->staff)->post(route('shelter.campaigns.reports.store', $campaign), [
            'title' => 'Первичный осмотр и рентген',
            'body' => "Ромашку осмотрел хирург.\n— приём хирурга — 8 000 ₸\n— рентген — 18 000 ₸",
            'spent' => '26 000',
            'photos' => [$this->png('a.png')],
            'receipts' => [$this->png('b.png'), $this->png('c.png')],
        ])->assertRedirect(route('shelter.campaigns.reports.index', $campaign))->assertSessionHas('status', 'Отчёт опубликован');

        $report = CampaignReport::sole();
        $this->assertSame(26_000 * 100, $report->spent_amount);
        $this->assertNotNull($report->published_at);
        $this->assertSame([AttachmentType::Photo, AttachmentType::Receipt, AttachmentType::Receipt], $report->attachments->pluck('type')->all());
        $report->attachments->each(fn ($a) => Storage::disk('public')->assertExists($a->path));

        $this->get(route('campaigns.show', $campaign))->assertOk()
            ->assertSee('Первичный осмотр и рентген')->assertSee('Приём хирурга')->assertSee("2\u{00A0}чека");

        $paths = $report->attachments->pluck('path')->all();
        $this->actingAs($this->staff)->delete(route('shelter.campaigns.reports.destroy', [$campaign, $report]))->assertSessionHas('status', 'Отчёт удалён');
        $this->assertSame(0, CampaignReport::count());
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_report_validation_and_foreign_report(): void
    {
        $campaign = DonationCampaign::factory()->for($this->shelter)->create();
        $files = array_map(fn ($i) => $this->png("p{$i}.png"), range(1, 6));

        $this->actingAs($this->staff)->post(route('shelter.campaigns.reports.store', $campaign), [
            'title' => 'Отчёт', 'body' => 'Слишком коротко', 'photos' => $files, 'receipts' => $files,
        ])->assertSessionHasErrors(['body', 'photos']);
        $this->assertSame(0, CampaignReport::count());

        $foreign = DonationCampaign::factory()->create();
        $report = $foreign->reports()->create(['title' => 'Чужой', 'body' => 'Чужой отчёт', 'published_at' => now()]);
        $this->actingAs($this->staff)->delete(route('shelter.campaigns.reports.destroy', [$campaign, $report]))->assertNotFound();
        $this->assertSame(1, CampaignReport::count());
    }

    public function test_donations_list_filters_and_csv_export(): void
    {
        $campaign = DonationCampaign::factory()->for($this->shelter)->create(['title' => 'Корм на зиму']);
        Donation::factory()->forCampaign($campaign)->create(['donor_name' => 'Ержан', 'donor_email' => 'erzhan@mail.kz', 'amount' => 5_000 * 100]);
        Donation::factory()->forCampaign($campaign)->anonymous()->create(['donor_name' => 'Тайный', 'amount' => 1_000 * 100, 'comment' => '=HYPERLINK("x")']);
        Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Failed)->create(['donor_name' => 'Отказ', 'amount' => 700 * 100]);
        Donation::factory()->create(['shelter_id' => $this->shelter->id, 'donor_name' => 'Фонд', 'amount' => 2_000 * 100]);
        Donation::factory()->create(['donor_name' => 'Чужой приют']);

        $this->actingAs($this->staff)->get('/shelter/donations')->assertOk()
            ->assertSee('Ержан')->assertSee('erzhan@mail.kz')->assertSee('Тайный')->assertSee('аноним на сайте')
            ->assertSee('Фонд')->assertDontSee('Чужой приют')
            ->assertSee(Money::format(800_000)); // оплачено: 5 000 + 1 000 + 2 000

        $this->actingAs($this->staff)->get('/shelter/donations?campaign=fund')->assertOk()->assertSee('Фонд')->assertDontSee('Ержан');
        $this->actingAs($this->staff)->get('/shelter/donations?status=failed')->assertOk()->assertSee('Отказ')->assertDontSee('Ержан');
        $this->actingAs($this->staff)->get('/shelter/donations?from='.now()->addDay()->format('Y-m-d'))->assertOk()->assertSee('По этим фильтрам ничего нет');

        $response = $this->actingAs($this->staff)->get('/shelter/donations/export?campaign='.$campaign->id);
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Ержан;erzhan@mail.kz', $csv);
        $this->assertStringContainsString('Корм на зиму', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'формулы экранируются');
        $this->assertStringNotContainsString('Фонд', $csv);
        $this->assertStringNotContainsString('Чужой приют', $csv);

        $this->actingAs($this->volunteer())->get('/shelter/donations/export')->assertForbidden();
    }
}
