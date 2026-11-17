<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * SEO-мелочи для <head>: адрес страницы без мусора в query, абсолютные ссылки на картинки
 * для Open Graph (мессенджеры не понимают относительные пути).
 */
final class Seo
{
    public const DEFAULT_DESCRIPTION = 'VetMoment — анкеты питомцев из приютов Казахстана, сборы на лечение и корм, запись на выгул собак.';

    /** Картинка для шаринга по умолчанию (1200×630, собирается design/tools/og-image.mjs). */
    public const DEFAULT_IMAGE = '/images/og-default.png';

    /**
     * Канонический адрес: текущий путь без query, кроме номера страницы списка.
     * Фильтры каталога — варианты одной страницы, а не отдельные страницы для поисковика.
     */
    public static function canonical(): string
    {
        $url = url()->current();
        $page = (int) request()->query('page', 1);

        return $page > 1 ? $url.'?page='.$page : $url;
    }

    public static function absolute(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
