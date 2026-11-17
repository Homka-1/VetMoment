<?php

namespace App\Http\Controllers;

use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * sitemap.xml и robots.txt. В sitemap — только публичные страницы, которые открыты для индексации
 * (те же скоупы, что решают, показывать ли noindex на странице). Список кэшируется на час.
 */
class SeoController extends Controller
{
    public const SITEMAP_CACHE_KEY = 'seo.sitemap';

    public function sitemap(): Response
    {
        $xml = Cache::remember(self::SITEMAP_CACHE_KEY, now()->addHour(), function (): string {
            $urls = collect([
                ['loc' => route('home'), 'priority' => '1.0'],
                ['loc' => route('pets.index'), 'priority' => '0.9'],
                ['loc' => route('campaigns.index'), 'priority' => '0.9'],
                ['loc' => route('walks.index'), 'priority' => '0.8'],
                ['loc' => route('shelters.index'), 'priority' => '0.7'],
                ['loc' => route('help'), 'priority' => '0.5'],
                ['loc' => route('privacy'), 'priority' => '0.2'],
            ]);

            $urls = $urls
                ->concat(Pet::query()->visible()->orderBy('id')->get(['id', 'slug', 'updated_at'])
                    ->map(fn (Pet $pet) => ['loc' => route('pets.show', $pet), 'lastmod' => $pet->updated_at, 'priority' => '0.8']))
                ->concat(DonationCampaign::query()->visible()->orderBy('id')->get(['id', 'slug', 'updated_at'])
                    ->map(fn (DonationCampaign $c) => ['loc' => route('campaigns.show', $c), 'lastmod' => $c->updated_at, 'priority' => '0.8']))
                ->concat(Shelter::query()->approved()->orderBy('id')->get(['id', 'slug', 'updated_at'])
                    ->map(fn (Shelter $s) => ['loc' => route('shelters.show', $s), 'lastmod' => $s->updated_at, 'priority' => '0.6']));

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Кабинеты, формы и служебные страницы: закрыты и meta-тегом, здесь — чтобы не тратить обход.
            'Disallow: /cabinet',
            'Disallow: /shelter$',
            'Disallow: /shelter/',
            'Disallow: /admin',
            'Disallow: /notifications',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /payments/',
            'Disallow: /donations/',
            'Disallow: /styleguide',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
