<?php

namespace Tests\Feature\Ui;

use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Этап 13: все основные страницы открываются на демо-данных под каждой ролью.
 * Вне production включён Model::preventLazyLoading — забытый with() в списке здесь даёт 500, а не тихий N+1.
 */
class DemoPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_pages_render_on_demo_data_for_every_role(): void
    {
        $this->seed(DemoSeeder::class);

        $pet = Pet::query()->inCatalog()->firstOrFail();
        $campaign = DonationCampaign::query()->visible()->firstOrFail();
        $shelter = Shelter::query()->approved()->firstOrFail();

        $public = ['/', '/pets', '/pets?page=2', '/campaigns', '/campaigns?tab=finished', '/walks', '/shelters', '/help',
            '/privacy', '/sitemap.xml', '/robots.txt', route('pets.show', $pet), route('campaigns.show', $campaign),
            route('campaigns.donations', $campaign), route('shelters.show', $shelter), route('walks.index', ['shelter' => $shelter->slug])];

        $byRole = [
            'volunteer' => ['/cabinet', '/cabinet/walks', '/cabinet/applications', '/cabinet/donations', '/cabinet/favorites',
                '/cabinet/profile', '/notifications'],
            'shelter' => ['/shelter', '/shelter/pets', '/shelter/applications', '/shelter/campaigns', '/shelter/donations',
                '/shelter/slots', '/shelter/bookings', '/shelter/profile', '/notifications'],
            'admin' => ['/admin', '/admin/shelters', '/admin/pets', '/admin/campaigns', '/admin/applications', '/admin/users'],
        ];

        foreach ($public as $url) {
            $this->get($url)->assertOk();
        }

        foreach ($byRole as $login => $urls) {
            $this->actingAs(User::query()->where('login', $login)->firstOrFail());
            foreach ([...$urls, ...$public] as $url) {
                $response = $this->get($url);
                $this->assertSame(200, $response->getStatusCode(), "{$login}: {$url}");
            }
        }
    }
}
