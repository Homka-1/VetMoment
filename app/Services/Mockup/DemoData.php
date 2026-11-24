<?php

namespace App\Services\Mockup;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\User;
use App\Models\WalkSlot;
use App\Services\PetCatalog;
use App\Support\LocalTime;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

/**
 * Данные для JS макета (window.DEMO_DATA в assets/data.js): что нужно, чтобы «пожертвовать»,
 * «записаться», «подать заявку», фильтровать каталог и подменять шапку роли без сервера.
 * Готовые куски разметки (бейджи, карточки, миниатюры) рендерятся теми же Blade-компонентами.
 */
class DemoData
{
    /**
     * @param  array<string, array{path: string, query: array<string, mixed>, role: string, html: string}>  $pages
     */
    public function __construct(
        private readonly PageMap $map,
        private readonly HtmlRewriter $rewriter,
        private readonly array $pages,
        private readonly ?\Closure $resolve = null,
    ) {}

    /**
     * @param  array<string, User|null>  $users
     * @param  array<string, array{header: string, menu: string, footer: string}>  $chrome
     * @return array<string, mixed>
     */
    public function build(array $users, array $chrome): array
    {
        $roleLabels = [PageMap::GUEST => 'Гость', PageMap::VOLUNTEER => 'Волонтёр', PageMap::SHELTER => 'Приют', PageMap::ADMIN => 'Админ'];
        $homes = [PageMap::VOLUNTEER => 'cabinet', PageMap::SHELTER => 'shelter', PageMap::ADMIN => 'admin'];
        $roles = [];
        foreach ($users as $role => $user) {
            $roles[$role] = [
                'label' => $roleLabels[$role],
                'name' => $user?->name,
                'login' => $user?->login,
                'home' => isset($homes[$role]) ? $this->map->file($homes[$role], [], $role) : 'index.html',
            ];
        }

        $routes = [];
        foreach ($this->pages as $file => $page) {
            $key = PageMap::key($page['path'], $page['query']);
            $routes[in_array($page['role'], [PageMap::SHELTER, PageMap::ADMIN], true) && str_contains($file, '--as-') ? $key.'@'.$page['role'] : $key] = $file;
        }

        return [
            'exportedAt' => now()->toIso8601String(),
            'exportedAtText' => LocalTime::date(now()),
            'roles' => $roles,
            'chrome' => $chrome,
            'routes' => $routes,
            'pets' => $this->pets(),
            'catalogOrder' => $this->catalogOrder(),
            'favorites' => $users[PageMap::VOLUNTEER]?->favoritePets()->pluck('slug')->all() ?? [],
            'campaigns' => $this->campaigns(),
            'slots' => $this->slots(),
            'ui' => $this->ui(),
        ];
    }

    /** @return array<string, array<string, mixed>> slug → данные анкеты для фильтров, строк кабинета и избранного */
    private function pets(): array
    {
        $cards = $this->catalogCards();
        $now = now();
        $pets = [];

        foreach (Pet::query()->visible()->with(['shelter', 'cover'])->get() as $pet) {
            $age = null;
            if ($pet->birth_date) {
                $years = $pet->birth_date->diffInYears($now);
                foreach (PetCatalog::AGES as $key => $range) {
                    if ($years >= $range['from'] && ($range['to'] === null || $years < $range['to'])) {
                        $age = $key;
                    }
                }
            }

            $pets[$pet->slug] = [
                'id' => $pet->id,
                'name' => $pet->name,
                'file' => $this->map->file('pets/'.$pet->slug, [], PageMap::GUEST),
                'species' => $pet->species->value,
                'sex' => $pet->sex->value,
                'size' => $pet->size?->value,
                'age' => $age,
                'city' => $pet->shelter->city,
                'shelter' => $pet->shelter->slug,
                'shelterName' => $pet->shelter->name,
                'walk' => $pet->isWalkable(),
                'guardianship' => (bool) $pet->guardianship_available,
                'thumb' => $this->fragment('<x-pet-thumb :pet="$pet" size="sm" class="rounded-pill!" />', ['pet' => $pet]),
                'card' => $cards[$pet->slug] ?? null,
            ];
        }

        return $pets;
    }

    /** @return array<string, list<string>> сортировка → slug-и в порядке каталога */
    private function catalogOrder(): array
    {
        return collect(PetCatalog::SORTS)->keys()->mapWithKeys(fn (string $sort) => [$sort => $this->sorted($sort)])->all();
    }

    /** @return list<string> */
    private function sorted(string $sort): array
    {
        $query = Pet::query()->inCatalog();
        if ($sort === 'new') {
            $query->latest('created_at')->latest('id');
        } else {
            $query->orderByRaw('CASE WHEN arrived_at IS NULL THEN 1 ELSE 0 END')->orderBy('arrived_at')->orderBy('id');
        }

        return $query->pluck('slug')->all();
    }

    /** Карточки каталога (уже переписанные ссылки) — для страницы «Избранное». */
    private function catalogCards(): array
    {
        $page = collect($this->pages)->first(fn (array $p) => $p['path'] === 'pets' && $p['query'] === []);
        if ($page === null) {
            return [];
        }

        $doc = HTMLDocument::createFromString($page['html'], LIBXML_NOERROR);
        $cards = [];
        foreach ($doc->querySelectorAll('[data-pet-catalog] > li') as $li) {
            $link = $li->querySelector('h3 a, h2 a');
            $parsed = $link ? $this->map->parse($link->getAttribute('href') ?? '') : null;
            if ($parsed !== null) {
                $cards[Str::after($parsed['path'], 'pets/')] = $this->rewriter->fragment($li->innerHTML, PageMap::GUEST);
            }
        }

        return $cards;
    }

    /** @return array<int, array<string, mixed>> */
    private function campaigns(): array
    {
        $campaigns = [];
        foreach (DonationCampaign::query()->visible()->with('shelter')->get() as $campaign) {
            $campaigns[$campaign->id] = [
                'title' => $campaign->title,
                'file' => $this->map->file('campaigns/'.$campaign->slug, [], PageMap::GUEST),
                'slug' => $campaign->slug,
                'shelterName' => $campaign->shelter->name,
                'raised' => (int) $campaign->raised_amount,
                'goal' => (int) $campaign->goal_amount,
                'donors' => (int) $campaign->donations_count,
            ];
        }

        return $campaigns;
    }

    /** @return array<int, array<string, mixed>> */
    private function slots(): array
    {
        $slots = [];
        foreach (WalkSlot::query()->upcoming()->with('shelter')->get() as $slot) {
            $slots[$slot->id] = [
                'file' => $this->pageFile('walks/slots/'.$slot->id, PageMap::VOLUNTEER),
                'capacity' => (int) $slot->capacity,
                'booked' => (int) $slot->booked_count,
                'iso' => LocalTime::iso($slot->starts_at),
                'when' => Str::ucfirst(LocalTime::weekdayShort($slot->starts_at)).', '.LocalTime::date($slot->starts_at),
                'range' => LocalTime::timeRange($slot->starts_at, $slot->ends_at),
                'shelterName' => $slot->shelter->name,
                'city' => $slot->shelter->city,
            ];
        }

        return $slots;
    }

    /** Готовые куски разметки для строк, которые JS добавляет в кабинет. */
    private function ui(): array
    {
        $badge = fn ($status) => $this->fragment('<x-badge :status="$status" />', ['status' => $status]);

        return [
            'badges' => [
                'booking' => $badge(BookingStatus::Pending),
                'application' => $badge(ApplicationStatus::New),
                'donation' => $badge(PaymentStatus::Succeeded),
            ],
            'types' => collect(ApplicationType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all(),
            'anyDog' => $this->fragment('<x-pet-thumb size="sm" class="rounded-pill!" />'),
            'toasts' => collect(['success', 'info', 'danger'])->mapWithKeys(fn (string $tone) => [
                $tone => $this->fragment('<x-toast :tone="$tone" title="" :autohide="false"><span data-toast-text></span></x-toast>', ['tone' => $tone]),
            ])->all(),
            'toastRegion' => '<div id="toasts" class="pointer-events-none fixed inset-x-0 top-0 z-50 flex flex-col items-end gap-3 p-4 sm:top-4 sm:right-4 sm:left-auto sm:w-[400px] sm:p-0" aria-live="polite"></div>',
            'withdrawButton' => $this->fragment('<x-button variant="ghost" size="sm">Отозвать заявку</x-button>'),
            'openButton' => $this->fragment('<x-button variant="secondary" size="sm" href="#">Открыть</x-button>'),
        ];
    }

    /** Файл страницы в макете с учётом редиректов (слот, куда волонтёр уже записан, ведёт на его запись) или null. */
    private function pageFile(string $path, string $role): ?string
    {
        $resolved = $this->resolve ? ($this->resolve)($path, [], '', $role) : ['type' => 'missing', 'file' => ''];

        return $resolved['type'] === 'page' ? $resolved['file'] : null;
    }

    /** @param  array<string, mixed>  $data */
    private function fragment(string $blade, array $data = []): string
    {
        return $this->rewriter->fragment(Blade::render($blade, $data), PageMap::GUEST);
    }
}
