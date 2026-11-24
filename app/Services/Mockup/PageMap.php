<?php

namespace App\Services\Mockup;

use Illuminate\Support\Carbon;

/**
 * Адреса сайта → файлы статического демо-макета (этап 15).
 *
 * Все страницы лежат плоско в корне папки mockup/: «/» → index.html, «/pets/bublik» → pets-bublik.html,
 * «/walks?shelter=dom» → walks--shelter-dom.html. Страницы кабинетов, общие для нескольких ролей
 * (уведомления, профиль), выгружаются для каждой роли отдельно: notifications--as-shelter.html.
 */
final class PageMap
{
    public const GUEST = 'guest';

    public const VOLUNTEER = 'volunteer';

    public const SHELTER = 'shelter';

    public const ADMIN = 'admin';

    /** Публичные адреса, query которых обрабатывает JS макета, а не отдельные файлы. */
    private const JS_QUERY_PATHS = ['pets'];

    /** Адреса, которых в макете нет (служебные, скачивание файлов, внешняя оплата, выход). */
    private const EXCLUDED = [
        '#^sitemap\.xml$#', '#^robots\.txt$#', '#\.ics$#', '#^payments/#', '#^styleguide$#',
        '#^reset-password#', '#^logout$#', '#^up$#', '#/export$#', '#\.csv$#',
    ];

    /** Параметры, которые в макете не меняют страницу (выбранный слот, подсказка собаки) — отбрасываются. */
    private const DROP_PARAMS = [
        '#^walks$#' => ['slot'],
        '#^walks/slots/\d+$#' => ['pet'],
    ];

    /** Недели календарей (?week=) — только в этом окне вокруг даты выгрузки, иначе обход бесконечен. */
    private const WEEKS_BACK = 1;

    private const WEEKS_FORWARD = 3;

    /** Страницы списков (?page=) — не дальше этой. */
    private const MAX_PAGE = 6;

    /** @var list<string> */
    private array $hosts;

    public function __construct(string $appUrl)
    {
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $this->hosts = array_values(array_unique([$host, $port ? "{$host}:{$port}" : $host, 'localhost', '127.0.0.1']));
    }

    /**
     * Разбирает ссылку сайта. null — внешняя ссылка, якорь, mailto:, tel: и т.п.
     *
     * @return array{path: string, query: array<string, mixed>, fragment: string}|null
     */
    public function parse(string $href): ?array
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#') || preg_match('#^(mailto|tel|javascript|data|blob):#i', $href)) {
            return null;
        }

        $parts = parse_url($href);
        if ($parts === false) {
            return null;
        }
        if (isset($parts['host'])) {
            $host = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            if (! in_array($host, $this->hosts, true) && ! in_array($parts['host'], $this->hosts, true)) {
                return null; // чужой сайт
            }
        } elseif (! str_starts_with($href, '/')) {
            return null; // уже относительная ссылка
        }

        parse_str($parts['query'] ?? '', $query);

        $path = trim(rawurldecode($parts['path'] ?? '/'), '/');
        foreach (self::DROP_PARAMS as $pattern => $params) {
            if (preg_match($pattern, $path)) {
                $query = array_diff_key($query, array_flip($params));
            }
        }

        return [
            'path' => $path,
            'query' => self::cleanQuery($query),
            'fragment' => $parts['fragment'] ?? '',
        ];
    }

    /** Файлы (картинки, шрифты, сборка) — копируются как есть. */
    public function isAsset(string $path): bool
    {
        return (bool) preg_match('#^(build|storage|images|fonts)/#', $path)
            || (bool) preg_match('#^[^/]+\.(svg|png|ico|jpg|webp)$#', $path);
    }

    public function isExcluded(string $path): bool
    {
        foreach (self::EXCLUDED as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Вариант страницы входит в макет: недели календаря — рядом с датой выгрузки, страницы списка — первые.
     *
     * @param  array<string, mixed>  $query
     */
    public function isAllowed(array $query): bool
    {
        if (isset($query['week'])) {
            $week = Carbon::make(is_string($query['week']) ? $query['week'] : null);
            $monday = now(config('app.display_timezone'))->startOfWeek();
            if ($week === null || $week->lt($monday->copy()->subWeeks(self::WEEKS_BACK)) || $week->gt($monday->copy()->addWeeks(self::WEEKS_FORWARD))) {
                return false;
            }
        }

        return ! isset($query['page']) || (int) $query['page'] <= self::MAX_PAGE;
    }

    /** Query этой страницы фильтрует JS (каталог) — файл один, query остаётся в ссылке. */
    public function queryHandledByJs(string $path): bool
    {
        return in_array($path, self::JS_QUERY_PATHS, true);
    }

    /**
     * Под какой ролью выгружается страница. $context — роль страницы, с которой ведёт ссылка:
     * уведомления и профиль у каждой роли свои.
     */
    public function roleFor(string $path, string $context = self::GUEST): string
    {
        if ($path === 'shelter' || str_starts_with($path, 'shelter/')) {
            return self::SHELTER;
        }
        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            return self::ADMIN;
        }
        if ($this->isShared($path)) {
            return in_array($context, [self::SHELTER, self::ADMIN], true) ? $context : self::VOLUNTEER;
        }
        if ($path === 'cabinet' || str_starts_with($path, 'cabinet/')
            || preg_match('#^walks/(slots|bookings)/#', $path) || preg_match('#^pets/[^/]+/apply$#', $path)) {
            return self::VOLUNTEER;
        }

        return self::GUEST;
    }

    /** Страница только для гостя (после входа сайт уводит в кабинет). */
    public function isGuestOnly(string $path): bool
    {
        return in_array($path, ['login', 'register', 'forgot-password'], true);
    }

    /**
     * Имя файла страницы.
     *
     * @param  array<string, mixed>  $query
     */
    public function file(string $path, array $query, string $role): string
    {
        $name = $path === '' ? 'index' : str_replace('/', '-', $path);
        $query = self::cleanQuery($query);
        if ($query !== [] && ! $this->queryHandledByJs($path)) {
            foreach ($query as $key => $value) {
                $name .= '--'.$key.'-'.(is_array($value) ? implode('-', $value) : $value);
            }
        }
        if ($this->isShared($path) && in_array($role, [self::SHELTER, self::ADMIN], true)) {
            $name .= '--as-'.$role;
        }

        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name);

        return trim(substr($name, 0, 150), '-').'.html';
    }

    /**
     * Ключ адреса для поиска файла из JS: «walks?shelter=dom&week=2026-11-23» (ключи по алфавиту).
     *
     * @param  array<string, mixed>  $query
     */
    public static function key(string $path, array $query = []): string
    {
        $query = self::cleanQuery($query);

        return $query === [] ? $path : $path.'?'.http_build_query($query);
    }

    /**
     * Пустые значения убраны, ключи отсортированы — одинаковые адреса дают одинаковый ключ.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public static function cleanQuery(array $query): array
    {
        $query = array_filter($query, fn ($v) => is_array($v) ? $v !== [] : (string) $v !== '');
        if ((string) ($query['page'] ?? '') === '1') {
            unset($query['page']);
        }
        ksort($query);

        return $query;
    }

    private function isShared(string $path): bool
    {
        return $path === 'notifications' || str_starts_with($path, 'notifications/') || $path === 'cabinet/profile';
    }
}
