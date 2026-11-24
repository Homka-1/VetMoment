<?php

namespace Tests\Unit\Mockup;

use App\Services\Mockup\PageMap;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Этап 15: адреса сайта → файлы статического демо-макета. */
class PageMapTest extends TestCase
{
    private PageMap $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->map = new PageMap('http://localhost:8000');
    }

    public function test_internal_links_are_parsed_and_external_ignored(): void
    {
        $this->assertSame(['path' => 'pets/bublik', 'query' => [], 'fragment' => 'donate'],
            $this->map->parse('http://localhost:8000/pets/bublik#donate'));
        $this->assertSame('walks', $this->map->parse('/walks?shelter=dom&slot=5')['path']);
        $this->assertSame(['shelter' => 'dom'], $this->map->parse('/walks?shelter=dom&slot=5')['query'], 'выбранный слот не создаёт отдельную страницу');
        $this->assertSame([], $this->map->parse('/cabinet/walks?page=1')['query'], 'первая страница списка — та же страница');

        foreach (['https://example.com/pets', 'mailto:hello@vetmoment.kz', 'tel:+77011112233', '#content', 'pets.html'] as $href) {
            $this->assertNull($this->map->parse($href), $href);
        }
    }

    public function test_file_names_are_flat_and_relative(): void
    {
        $this->assertSame('index.html', $this->map->file('', [], PageMap::GUEST));
        $this->assertSame('pets-bublik-21ke.html', $this->map->file('pets/bublik-21ke', [], PageMap::GUEST));
        $this->assertSame('walks--shelter-dom--week-2026-11-23.html', $this->map->file('walks', ['week' => '2026-11-23', 'shelter' => 'dom'], PageMap::GUEST));
        $this->assertSame('pets.html', $this->map->file('pets', ['species' => ['cat']], PageMap::GUEST), 'фильтры каталога — в JS');
        $this->assertSame('notifications--as-shelter.html', $this->map->file('notifications', [], PageMap::SHELTER));
        $this->assertSame('notifications.html', $this->map->file('notifications', [], PageMap::VOLUNTEER));
    }

    public function test_pages_are_rendered_for_the_right_role(): void
    {
        $this->assertSame(PageMap::GUEST, $this->map->roleFor('pets/bublik', PageMap::VOLUNTEER));
        $this->assertSame(PageMap::VOLUNTEER, $this->map->roleFor('pets/bublik/apply'));
        $this->assertSame(PageMap::VOLUNTEER, $this->map->roleFor('walks/slots/5'));
        $this->assertSame(PageMap::VOLUNTEER, $this->map->roleFor('cabinet/walks', PageMap::SHELTER));
        $this->assertSame(PageMap::SHELTER, $this->map->roleFor('shelter/pets'));
        $this->assertSame(PageMap::GUEST, $this->map->roleFor('shelters/dom'));
        $this->assertSame(PageMap::ADMIN, $this->map->roleFor('admin/users'));
        $this->assertSame(PageMap::ADMIN, $this->map->roleFor('notifications', PageMap::ADMIN));
        $this->assertSame(PageMap::VOLUNTEER, $this->map->roleFor('notifications', PageMap::GUEST));
    }

    public function test_service_pages_and_far_weeks_are_left_out(): void
    {
        foreach (['sitemap.xml', 'logout', 'payments/fake/abc', 'walks/bookings/1/calendar.ics', 'shelter/donations/export'] as $path) {
            $this->assertTrue($this->map->isExcluded($path), $path);
        }
        $this->assertFalse($this->map->isExcluded('walks'));

        Carbon::setTestNow('2026-11-24 12:00:00');
        $this->assertTrue($this->map->isAllowed(['week' => '2026-11-30']));
        $this->assertFalse($this->map->isAllowed(['week' => '2027-03-01']));
        $this->assertFalse($this->map->isAllowed(['page' => '40']));
        Carbon::setTestNow();
    }

    public function test_route_keys_do_not_depend_on_parameter_order(): void
    {
        $this->assertSame(PageMap::key('walks', ['week' => 'w', 'shelter' => 's']), PageMap::key('walks', ['shelter' => 's', 'week' => 'w', 'pet' => '']));
    }
}
