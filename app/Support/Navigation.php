<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Пункты навигации сайта и кабинетов — в одном месте для шапки, мобильного меню, подвала и бокового меню.
 *
 * Разделы, чьи маршруты появятся на следующих этапах (каталог — 8, сборы — 9, выгул — 10, кабинеты — 11),
 * ссылаются на URL из карты сайта (docs/product/sitemap.md), пока маршрута нет.
 */
final class Navigation
{
    /**
     * Основные разделы сайта.
     *
     * @return list<array{label: string, href: string, icon: string, active: bool}>
     */
    public static function main(): array
    {
        return [
            self::item('Питомцы', 'pets.index', '/pets', 'paw', 'pets*'),
            self::item('Сборы', 'campaigns.index', '/campaigns', 'tenge', 'campaigns*'),
            self::item('Выгул', 'walks.index', '/walks', 'leash', 'walks*'),
            self::item('Приюты', 'shelters.index', '/shelters', 'home', 'shelters*'),
            self::item('Как помочь', 'help', '/help', 'heart', 'help'),
        ];
    }

    /** Ссылка жёлтой кнопки «Помочь» в шапке. */
    public static function helpUrl(): string
    {
        return self::url('help', '/help');
    }

    /**
     * Боковое меню кабинета по роли. Показываются только разделы, у которых уже есть маршрут.
     *
     * @return list<array{label: string, href: string, icon: string, active: bool}>
     */
    public static function cabinet(User $user): array
    {
        $items = match ($user->role) {
            Role::Admin => [
                ['Обзор', 'admin.dashboard', 'home', 'admin'],
                ['Приюты', 'admin.shelters.index', 'shield-check', 'admin/shelters*'],
                ['Пользователи', 'admin.users.index', 'users', 'admin/users*'],
            ],
            Role::ShelterStaff => [
                ['Сводка', 'shelter.dashboard', 'home', 'shelter'],
                ['Питомцы', 'shelter.pets.index', 'paw', 'shelter/pets*'],
                ['Заявки', 'shelter.applications.index', 'mail', 'shelter/applications*'],
                ['Сборы', 'shelter.campaigns.index', 'tenge', 'shelter/campaigns*'],
                ['Слоты выгула', 'shelter.slots.index', 'calendar', 'shelter/slots*'],
                ['Записи на выгул', 'shelter.bookings.index', 'leash', 'shelter/bookings*'],
                ['Профиль приюта', 'shelter.profile', 'edit', 'shelter/profile*'],
            ],
            Role::Volunteer => [
                ['Обзор', 'cabinet.dashboard', 'home', 'cabinet'],
                ['Мои выгулы', 'cabinet.walks', 'leash', 'cabinet/walks*'],
                ['Мои заявки', 'cabinet.applications', 'mail', 'cabinet/applications*'],
                ['Пожертвования', 'cabinet.donations', 'tenge', 'cabinet/donations*'],
                ['Избранное', 'cabinet.favorites', 'heart', 'cabinet/favorites*'],
                ['Профиль', 'cabinet.profile', 'user', 'cabinet/profile*'],
            ],
        };

        $result = [];

        foreach ($items as [$label, $route, $icon, $pattern]) {
            if (Route::has($route)) {
                $result[] = ['label' => $label, 'href' => route($route), 'icon' => $icon, 'active' => request()->is($pattern)];
            }
        }

        return $result;
    }

    /** URL маршрута, если он уже есть, иначе — адрес из карты сайта. */
    public static function url(string $route, string $fallbackPath): string
    {
        return Route::has($route) ? route($route) : url($fallbackPath);
    }

    /**
     * @return array{label: string, href: string, icon: string, active: bool}
     */
    private static function item(string $label, string $route, string $path, string $icon, string $pattern): array
    {
        return [
            'label' => $label,
            'href' => self::url($route, $path),
            'icon' => $icon,
            'active' => request()->is($pattern),
        ];
    }
}
