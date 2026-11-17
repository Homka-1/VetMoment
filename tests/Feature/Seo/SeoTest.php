<?php

namespace Tests\Feature\Seo;

use App\Enums\CampaignStatus;
use App\Enums\PetStatus;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Этап 13: мета-теги, Open Graph, noindex закрытых страниц, sitemap.xml и robots.txt.
 */
class SeoTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_public_page_has_canonical_and_open_graph(): void
    {
        $this->get('/pets?sort=new&page=2')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/pets').'?page=2">', false)
            ->assertSee('<meta property="og:site_name" content="VetMoment">', false)
            ->assertSee('<meta property="og:image" content="'.url('/images/og-default.png').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertDontSee('noindex');
    }

    public function test_pet_card_shares_its_photo_and_type_article(): void
    {
        $pet = Pet::factory()->for(Shelter::factory())->status(PetStatus::LookingForHome)->create(['name' => 'Пирожок']);
        $pet->photos()->create(['path' => 'pets/p.webp', 'thumb_path' => 'pets/p-thumb.webp', 'sort' => 1, 'is_cover' => true]);

        $this->get(route('pets.show', $pet))
            ->assertOk()
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:image" content="'.url('/storage/pets/p.webp').'">', false)
            ->assertSee('<meta property="og:url" content="'.route('pets.show', $pet).'">', false)
            ->assertDontSee('noindex');
    }

    public function test_campaign_page_has_open_graph(): void
    {
        $campaign = DonationCampaign::factory()->for(Shelter::factory())->status(CampaignStatus::Active)->create();

        $this->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:title" content="'.e($campaign->title).' — сбор приюта', false);
    }

    public function test_closed_pages_are_noindex_without_canonical(): void
    {
        $this->get('/login')->assertOk()->assertSee('content="noindex, nofollow"', false)->assertDontSee('rel="canonical"', false);

        $this->actingAs($this->volunteer())->get('/cabinet')
            ->assertOk()->assertSee('content="noindex, nofollow"', false);
        $this->get('/notifications')->assertOk()->assertSee('content="noindex, nofollow"', false);
    }

    public function test_hidden_pet_preview_is_noindex(): void
    {
        $shelter = Shelter::factory()->create();
        $pet = Pet::factory()->for($shelter)->hidden()->create();

        $this->actingAs($this->staffOf($shelter))->get(route('pets.show', $pet))
            ->assertOk()->assertSee('content="noindex, nofollow"', false);
    }

    public function test_sitemap_lists_only_public_pages(): void
    {
        $shelter = Shelter::factory()->create();
        $visible = Pet::factory()->for($shelter)->status(PetStatus::LookingForHome)->create();
        $hidden = Pet::factory()->for($shelter)->hidden()->create();
        $draft = Pet::factory()->for($shelter)->status(PetStatus::Draft)->create();
        $pendingShelter = Shelter::factory()->pending()->create();
        $campaign = DonationCampaign::factory()->for($shelter)->status(CampaignStatus::Active)->create();
        $draftCampaign = DonationCampaign::factory()->for($shelter)->status(CampaignStatus::Draft)->create();

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'sitemap — корректный XML');
        // Сравниваем пути: хост в ответе берётся из запроса, а не из APP_URL.
        $locs = [];
        foreach ($xml->url as $url) {
            $locs[] = parse_url((string) $url->loc, PHP_URL_PATH) ?? '/';
        }

        $this->assertContains('/', $locs);
        $this->assertContains(route('pets.show', $visible, absolute: false), $locs);
        $this->assertContains(route('campaigns.show', $campaign, absolute: false), $locs);
        $this->assertContains(route('shelters.show', $shelter, absolute: false), $locs);
        $this->assertNotContains(route('pets.show', $hidden, absolute: false), $locs);
        $this->assertNotContains(route('pets.show', $draft, absolute: false), $locs);
        $this->assertNotContains(route('campaigns.show', $draftCampaign, absolute: false), $locs);
        $this->assertNotContains(route('shelters.show', $pendingShelter, absolute: false), $locs);
    }

    public function test_robots_points_to_sitemap_and_closes_cabinets(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('sitemap'))
            ->assertSee('Disallow: /cabinet')
            ->assertSee('Disallow: /admin')
            ->assertDontSee('Disallow: /shelters');
    }
}
