<?php

namespace App\Services\Mockup;

use Closure;
use Dom\Element;
use Dom\HTMLDocument;

/**
 * Перепись HTML страниц сайта для статического макета: ссылки → относительные файлы, формы → обработчики
 * demo.js, сборка Vite → assets/*.css|js, плашка «Демо-версия» и переключатель ролей в начале страницы.
 */
class HtmlRewriter
{
    /** Пометка ссылки, страницы которой нет в макете: demo.js показывает подсказку вместо перехода. */
    public const MISSING = 'data-demo-missing';

    /**
     * @param  Closure(string, array<string, mixed>, string, string): array{type: string, file: string}  $resolve
     */
    public function __construct(private readonly PageMap $map, private readonly Closure $resolve) {}

    /**
     * Ссылки страницы — для обхода.
     *
     * @return list<string>
     */
    public function links(string $html): array
    {
        $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $links = [];
        foreach ($doc->querySelectorAll('a[href]') as $a) {
            $links[] = $a->getAttribute('href');
        }

        return $links;
    }

    public function page(string $html, string $role, string $file, bool $guestOnly = false): string
    {
        $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $root = $doc->documentElement;
        $root->setAttribute('data-demo-page', $file);
        $root->setAttribute('data-demo-role', $role);
        if ($guestOnly) {
            $root->setAttribute('data-demo-guest-only', '');
        }

        foreach ($doc->querySelectorAll('link[rel=preload], link[rel=modulepreload], link[rel=canonical], meta[property="og:url"], meta[name=csrf-token]') as $node) {
            $node->remove();
        }
        foreach ($doc->querySelectorAll('link[rel=stylesheet], script[src]') as $node) {
            $src = $node->getAttribute('href') ?? $node->getAttribute('src') ?? '';
            if (str_contains($src, '/build/') || str_contains($src, ':5173') || str_contains($src, '@vite')) {
                $node->remove();
            }
        }

        $head = $doc->head;
        $assets = HTMLDocument::createFromString('<!DOCTYPE html><html><head>'
            .'<link rel="stylesheet" href="assets/app.css"><link rel="stylesheet" href="assets/demo.css">'
            .'<script src="assets/data.js"></script><script src="assets/demo.js"></script>'
            .'<script src="assets/app.js" defer></script></head></html>', LIBXML_NOERROR);
        foreach (iterator_to_array($assets->head->childNodes) as $node) {
            $head->appendChild($doc->importNode($node, true));
        }

        $this->rewrite($doc->body, $role);
        foreach ($doc->head->querySelectorAll('link[href]') as $node) {
            $this->rewriteAttr($node, 'href', $role); // иконки сайта
        }

        $bar = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.self::banner().'</body></html>', LIBXML_NOERROR)->body->firstElementChild;
        $skip = $doc->querySelector('body > a[href="#content"]');
        $skip ? $skip->after($doc->importNode($bar, true)) : $doc->body->prepend($doc->importNode($bar, true));

        return $doc->saveHtml();
    }

    /** Кусок HTML (шапка роли, карточка, бейдж) с переписанными ссылками. */
    public function fragment(string $html, string $role): string
    {
        $doc = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR);
        $this->rewrite($doc->body, $role);

        return trim($doc->body->innerHTML);
    }

    private function rewrite(Element $root, string $role): void
    {
        foreach ($root->querySelectorAll('input[name=_token]') as $node) {
            $node->remove();
        }

        foreach ($root->querySelectorAll('a[href], link[href]') as $node) {
            $this->rewriteAttr($node, 'href', $role);
        }
        foreach ($root->querySelectorAll('img[src], source[src]') as $node) {
            $this->rewriteAttr($node, 'src', $role);
        }
        foreach ($root->querySelectorAll('img[srcset], source[srcset]') as $node) {
            $set = array_map(function (string $item) use ($role) {
                [$url, $size] = array_pad(preg_split('/\s+/', trim($item), 2), 2, '');

                return trim($this->url($url, $role)['href'].' '.$size);
            }, explode(',', $node->getAttribute('srcset')));
            $node->setAttribute('srcset', implode(', ', $set));
        }

        foreach ($root->querySelectorAll('form') as $form) {
            $action = $form->getAttribute('action') ?? '';
            $link = $this->map->parse($action);
            $method = strtoupper($form->getAttribute('method') ?? 'GET');
            if ($method === 'GET') {
                if ($link !== null) {
                    $form->setAttribute('data-demo-get', $link['path']);
                    $resolved = ($this->resolve)($link['path'], [], '', $role);
                    $form->setAttribute('action', $resolved['type'] === 'page' ? $resolved['file'] : '#');
                }

                continue;
            }
            $form->setAttribute('data-demo-action', $link['path'] ?? '');
            $form->setAttribute('action', '#');
        }
    }

    private function rewriteAttr(Element $node, string $attr, string $role): void
    {
        $result = $this->url($node->getAttribute($attr) ?? '', $role);
        $node->setAttribute($attr, $result['href']);
        if ($result['missing'] !== null) {
            $node->setAttribute(self::MISSING, $result['missing']);
        }
    }

    /**
     * @return array{href: string, missing: string|null}
     */
    private function url(string $href, string $role): array
    {
        $link = $this->map->parse($href);
        if ($link === null) {
            return ['href' => $href, 'missing' => null];
        }

        $resolved = ($this->resolve)($link['path'], $link['query'], $link['fragment'], $role);

        return match ($resolved['type']) {
            'asset', 'page' => ['href' => $resolved['file'], 'missing' => null],
            default => ['href' => '#', 'missing' => '/'.PageMap::key($link['path'], $link['query'])],
        };
    }

    /** Плашка «Демо-версия» с переключателем ролей (стили — resources/mockup/demo.css, логика — demo.js). */
    public static function banner(): string
    {
        $roles = [PageMap::GUEST => 'Гость', PageMap::VOLUNTEER => 'Волонтёр', PageMap::SHELTER => 'Приют', PageMap::ADMIN => 'Админ'];
        $buttons = '';
        foreach ($roles as $role => $label) {
            $buttons .= '<button type="button" class="demo-roles__item" data-demo-switch="'.$role.'" aria-pressed="false">'.$label.'</button>';
        }

        return '<div class="demo-bar" data-demo-bar role="region" aria-label="Демо-версия">'
            .'<div class="page-container demo-bar__inner">'
            .'<p class="demo-bar__note"><svg class="demo-bar__icon" width="20" height="20" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg>'
            .'<span><strong>Демо-версия:</strong> данные не сохраняются на сервере</span></p>'
            .'<div class="demo-bar__controls">'
            .'<span class="demo-bar__label" id="demo-roles-label">Смотреть как</span>'
            .'<div class="demo-roles" role="group" aria-labelledby="demo-roles-label">'.$buttons.'</div>'
            .'<button type="button" class="demo-bar__reset" data-demo-reset aria-label="Сбросить демо">Сбросить<span class="demo-bar__more"> демо</span></button>'
            .'</div></div></div>';
    }
}
