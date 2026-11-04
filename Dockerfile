# syntax=docker/dockerfile:1
# VetMoment — образ приложения. Всё нужное для запуска (PHP-зависимости, собранные
# CSS/JS) собирается внутри образа: на компьютере пользователя нужен только Docker.

# --- 1. PHP-зависимости (без dev-пакетов) ----------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist \
        --ignore-platform-reqs

# --- 2. Фронтенд-ассеты (Vite + Tailwind) -----------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
# Tailwind сканирует шаблоны пагинации Laravel (см. @source в app.css)
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# --- 3. Приложение: PHP 8.4 + Apache ----------------------------------------------
FROM php:8.4-apache AS app

# GD (jpeg, webp) — ресайз фото питомцев до 1600 px и превью 600 px (App\Services\PetPhotoStorage)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql zip gd exif \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# До 10 фото по 8 МБ в одной анкете → post_max_size 100M; память — на декодирование крупных фото в GD
RUN a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'upload_max_filesize=10M\npost_max_size=100M\nmax_file_uploads=20\nmemory_limit=384M\nexpose_php=Off\n' \
        > "$PHP_INI_DIR/conf.d/vetmoment.ini"

# Composer нужен в режиме разработки (docker-compose.dev.yml ставит dev-зависимости)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan package:discover --ansi \
    # На случай, если файл попал в образ с окончаниями строк Windows (CRLF)
    && sed -i 's/\r$//' docker/start.sh \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
        storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=10s --timeout=5s --start-period=300s --retries=6 \
    CMD curl -fsS http://localhost/up > /dev/null || exit 1

# Через sh — чтобы в режиме разработки (bind-mount с Windows) не зависеть от бита исполнения
ENTRYPOINT ["sh", "/var/www/html/docker/start.sh"]
CMD ["app"]
