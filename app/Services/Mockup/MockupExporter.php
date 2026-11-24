<?php

namespace App\Services\Mockup;

use App\Models\User;
use App\Models\WalkSlot;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Выгрузка сайта в статический демо-макет (этап 15): обход страниц под ролями «гость / волонтёр /
 * приют / админ», рендер через HTTP-ядро приложения (без веб-сервера), перепись ссылок в относительные
 * файлы, копирование стилей, скриптов, шрифтов и картинок, данные для JS — в assets/data.js.
 *
 * Результат открывается двойным кликом по index.html (протокол file://): без сервера, сети и ES-модулей.
 */
class MockupExporter
{
    /** Демо-логины ролей (DemoSeeder). */
    public const LOGINS = [
        PageMap::VOLUNTEER => 'volunteer',
        PageMap::SHELTER => 'shelter',
        PageMap::ADMIN => 'admin',
    ];

    /** Страницы, с которых начинается обход каждой роли; остальное находится по ссылкам. */
    private const SEEDS = [
        PageMap::GUEST => ['', 'pets', 'campaigns', 'walks', 'shelters', 'help', 'privacy', 'donate', 'login', 'register', 'forgot-password'],
        PageMap::VOLUNTEER => ['cabinet', 'cabinet/walks', 'cabinet/applications', 'cabinet/donations', 'cabinet/favorites',
            'cabinet/profile', 'cabinet/shelter-request', 'notifications'],
        PageMap::SHELTER => ['shelter', 'notifications', 'cabinet/profile'],
        PageMap::ADMIN => ['admin', 'notifications', 'cabinet/profile'],
    ];

    /** @var array<string, array{path: string, query: array<string, mixed>, role: string, html: string}> файл → страница */
    private array $pages = [];

    /** @var array<string, array{path: string, query: array<string, mixed>, fragment: string, role: string}> файл → куда редиректит */
    private array $aliases = [];

    /** @var array<string, true> адреса, которые не отдали страницу (403, 404, файл) */
    private array $failed = [];

    /** @var array<string, true> */
    private array $assets = [];

    /** @var array<string, User|null> */
    private array $users = [];

    /** @var list<string> */
    private array $warnings = [];

    private PageMap $map;

    private HtmlRewriter $rewriter;

    /** @var (callable(string): void)|null */
    private $log;

    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  callable(string): void|null  $log
     * @return array{pages: int, assets: int, bytes: int, warnings: list<string>}
     */
    public function export(string $target, int $limit = 1000, ?callable $log = null): array
    {
        $this->log = $log;
        $this->map = new PageMap((string) config('app.url'));
        $this->rewriter = new HtmlRewriter($this->map, $this->resolveLink(...));
        $this->users = [PageMap::GUEST => null];
        foreach (self::LOGINS as $role => $login) {
            $this->users[$role] = User::query()->where('login', $login)->firstOrFail();
        }

        $this->crawl($limit);

        $this->files->deleteDirectory($target);
        $this->files->ensureDirectoryExists($target.'/assets');

        foreach ($this->pages as $file => $page) {
            $html = $this->rewriter->page($page['html'], $page['role'], $file, $this->map->isGuestOnly($page['path']));
            $this->files->put($target.'/'.$file, $html);
        }

        $notFound = $this->render('demo-stranica-ne-naydena', [], PageMap::GUEST);
        $this->files->put($target.'/404.html', $this->rewriter->page($notFound->getContent(), PageMap::GUEST, '404.html'));

        $data = (new DemoData($this->map, $this->rewriter, $this->pages, $this->resolveLink(...)))->build($this->users, $this->chrome());
        $this->files->put($target.'/assets/data.js', "/* Данные демо-макета VetMoment — генерируется `php artisan mockup:export`, не редактировать вручную. */\n"
            .'window.DEMO_DATA = '.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).";\n");

        $this->copyAssets($target);

        $bytes = 0;
        foreach ($this->files->allFiles($target) as $f) {
            $bytes += $f->getSize();
        }

        return ['pages' => count($this->pages) + 1, 'assets' => count($this->assets), 'bytes' => $bytes, 'warnings' => $this->warnings];
    }

    /** Обход в ширину от SEEDS по ссылкам страниц. */
    private function crawl(int $limit): void
    {
        $queue = [];
        foreach (self::SEEDS as $role => $paths) {
            foreach ($paths as $path) {
                $queue[] = [$path, [], $this->map->roleFor($path, $role)];
            }
        }

        // Страницы слотов: на «Записи на выгул» слоты — радиокнопки формы, а не ссылки.
        foreach (WalkSlot::query()->upcoming()->pluck('id') as $id) {
            $queue[] = ['walks/slots/'.$id, [], PageMap::VOLUNTEER];
        }

        while ($queue !== []) {
            [$path, $query, $role] = array_shift($queue);
            $file = $this->map->file($path, $query, $role);
            if (isset($this->pages[$file]) || isset($this->aliases[$file]) || isset($this->failed[$file])) {
                continue;
            }
            if (count($this->pages) >= $limit) {
                $this->warnings[] = "Достигнут предел в {$limit} страниц — часть ссылок в макете не работает.";
                break;
            }

            $response = $this->render($path, $query, $role);
            $status = $response->getStatusCode();

            if ($response->isRedirect()) {
                $target = $this->map->parse((string) $response->headers->get('Location'));
                if ($target === null || $this->map->isExcluded($target['path'])) {
                    $this->failed[$file] = true;

                    continue;
                }
                $targetRole = $this->map->roleFor($target['path'], $role);
                $this->aliases[$file] = $target + ['role' => $targetRole];
                $queue[] = [$target['path'], $this->map->queryHandledByJs($target['path']) ? [] : $target['query'], $targetRole];

                continue;
            }

            if ($status !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                $this->failed[$file] = true;
                if ($status >= 500) {
                    $this->warnings[] = "/{$path} ({$role}): ответ {$status}";
                }

                continue;
            }

            $html = (string) $response->getContent();
            if ($path === 'pets' && $query === []) {
                $html = $this->mergeCatalog($html);
            }

            $this->pages[$file] = ['path' => $path, 'query' => $query, 'role' => $role, 'html' => $html];
            ($this->log)("{$file}  ←  /".PageMap::key($path, $query)." [{$role}]");

            foreach ($this->rewriter->links($html) as $href) {
                $link = $this->map->parse($href);
                if ($link === null || $this->map->isAsset($link['path']) || $this->map->isExcluded($link['path']) || ! $this->map->isAllowed($link['query'])) {
                    continue;
                }
                $linkQuery = $this->map->queryHandledByJs($link['path']) ? [] : $link['query'];
                $queue[] = [$link['path'], $linkQuery, $this->map->roleFor($link['path'], $role)];
            }
        }
    }

    /**
     * Куда ведёт ссылка в макете: файл страницы, файл-ассет или null (страницы в макете нет).
     *
     * @return array{type: 'page'|'asset'|'missing', file: string, query?: string}
     */
    public function resolveLink(string $path, array $query, string $fragment, string $context): array
    {
        if ($this->map->isAsset($path)) {
            $this->assets[$path] = true;

            return ['type' => 'asset', 'file' => $path];
        }
        if ($this->map->isExcluded($path)) {
            return ['type' => 'missing', 'file' => ''];
        }

        $role = $this->map->roleFor($path, $context);
        $byJs = $this->map->queryHandledByJs($path);
        $file = $this->map->file($path, $byJs ? [] : $query, $role);

        for ($hops = 0; isset($this->aliases[$file]) && $hops < 5; $hops++) {
            $alias = $this->aliases[$file];
            [$path, $query, $role] = [$alias['path'], $alias['query'], $alias['role']];
            $fragment = $alias['fragment'] ?: $fragment;
            $byJs = $this->map->queryHandledByJs($path);
            $file = $this->map->file($path, $byJs ? [] : $query, $role);
        }

        if (! isset($this->pages[$file])) {
            return ['type' => 'missing', 'file' => ''];
        }

        $suffix = ($byJs && $query !== [] ? '?'.http_build_query($query) : '').($fragment !== '' ? '#'.$fragment : '');

        return ['type' => 'page', 'file' => $file.$suffix];
    }

    /** Ответ приложения на GET-запрос от имени роли — тем же HTTP-ядром, что и у веб-сервера. */
    public function render(string $path, array $query, string $role): Response
    {
        $url = rtrim((string) config('app.url'), '/').'/'.$path.($query !== [] ? '?'.http_build_query($query) : '');
        $request = Request::create($url, 'GET');

        // Каждый запрос — с чистой сессией и своим пользователем (как отдельный посетитель).
        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');
        Auth::forgetGuards();
        $user = $this->users[$role] ?? null;
        if ($user !== null) {
            Auth::guard('web')->setUser($user->fresh());
        }

        $kernel = app(HttpKernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    /** Каталог в макете — все анкеты на одной странице (фильтры и сортировка — в JS) + пустое состояние. */
    private function mergeCatalog(string $html): string
    {
        $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $list = $doc->querySelector('[data-pet-catalog]');
        if (! $list instanceof Element) {
            return $html;
        }

        for ($page = 2; $page < 20; $page++) {
            $next = HTMLDocument::createFromString((string) $this->render('pets', ['page' => $page], PageMap::GUEST)->getContent(), LIBXML_NOERROR);
            $items = $next->querySelectorAll('[data-pet-catalog] > li');
            if ($items->length === 0) {
                break;
            }
            foreach ($items as $item) {
                $list->appendChild($doc->importNode($item, true));
            }
        }
        $doc->querySelector('[data-catalog-pagination]')?->remove();

        $emptyDoc = HTMLDocument::createFromString((string) $this->render('pets', ['q' => 'нет-такого-питомца'], PageMap::GUEST)->getContent(), LIBXML_NOERROR);
        $empty = $emptyDoc->querySelector('[data-catalog-empty]');
        if ($empty instanceof Element) {
            $node = $doc->importNode($empty, true);
            $node->setAttribute('hidden', '');
            $list->after($node);
        }

        return $doc->saveHtml();
    }

    /**
     * Шапка, мобильное меню и подвал каждой роли — JS макета подменяет их на публичных страницах,
     * которые выгружены от имени гостя.
     *
     * @return array<string, array{header: string, menu: string, footer: string}>
     */
    private function chrome(): array
    {
        $chrome = [];
        foreach (array_keys($this->users) as $role) {
            $doc = HTMLDocument::createFromString((string) $this->render('privacy', [], $role)->getContent(), LIBXML_NOERROR);
            $actions = $doc->querySelector('header .ml-auto');
            $menuParts = '';
            $nav = $doc->querySelector('#mobile-menu nav');
            for ($node = $nav?->nextElementSibling; $node !== null; $node = $node->nextElementSibling) {
                $menuParts .= $doc->saveHtml($node);
            }
            $footer = $doc->querySelector('footer');
            $chrome[$role] = [
                'header' => $this->rewriter->fragment($actions?->innerHTML ?? '', $role),
                'menu' => $this->rewriter->fragment($menuParts, $role),
                'footer' => $this->rewriter->fragment($footer?->innerHTML ?? '', $role),
            ];
        }

        return $chrome;
    }

    /** Сборка Vite (CSS, JS, шрифты), картинки и фото, а также файлы самого макета (resources/mockup). */
    private function copyAssets(string $target): void
    {
        $manifest = json_decode($this->files->get(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
        $css = $manifest['resources/css/app.css']['file'];
        $js = $manifest['resources/js/app.js']['file'];

        // Шрифты и прочее из CSS — рядом с app.css, ссылки url(/build/assets/x) → url(x).
        $style = $this->files->get(public_path('build/'.$css));
        $style = preg_replace_callback('#url\((["\']?)/build/assets/([^)"\']+)\1\)#', function (array $m) use ($target) {
            $this->files->copy(public_path('build/assets/'.$m[2]), $target.'/assets/'.$m[2]);

            return 'url('.$m[2].')';
        }, $style);
        $this->files->put($target.'/assets/app.css', $style);

        // Модули Vite не работают по file:// — тот же код как обычный скрипт в замыкании.
        $script = $this->files->get(public_path('build/'.$js));
        if (preg_match('/^\s*(import|export)\s/m', $script)) {
            $this->warnings[] = 'В сборке app.js есть import/export — по file:// скрипт может не заработать.';
        }
        $this->files->put($target.'/assets/app.js', "(function () {\n'use strict';\n".$script."\n})();\n");

        foreach (['demo.js', 'demo.css'] as $file) {
            $this->files->copy(resource_path('mockup/'.$file), $target.'/assets/'.$file);
        }
        $this->files->copy(resource_path('mockup/README.txt'), $target.'/README.txt');

        foreach (array_keys($this->assets) as $path) {
            $source = str_starts_with($path, 'storage/')
                ? storage_path('app/public/'.substr($path, 8))
                : public_path($path);
            if (str_starts_with($path, 'build/')) {
                continue; // сборка подключается assets/app.css и app.js
            }
            if (! $this->files->exists($source)) {
                $this->warnings[] = "Нет файла {$path}";

                continue;
            }
            $this->files->ensureDirectoryExists(dirname($target.'/'.$path));
            $this->files->copy($source, $target.'/'.$path);
        }
    }
}
