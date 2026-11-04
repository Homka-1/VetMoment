#!/bin/sh
# Точка входа контейнера VetMoment.
#   app   — веб-сервер (по умолчанию): готовит окружение, мигрирует БД, грузит демо-данные, запускает Apache;
#   queue — воркер очереди (ждёт, пока app подготовит окружение);
#   любая другая команда выполняется как есть.
set -e

cd /var/www/html

ROLE="${1:-app}"
KEY_FILE=storage/app/.app_key

log() { echo "[vetmoment] $*"; }

prepare_env() {
    # .env внутри контейнера — из примера; реальные значения приходят из переменных docker-compose.
    if [ ! -f .env ]; then
        cp .env.example .env
        log ".env создан из .env.example"
    fi

    mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
        storage/framework/testing storage/framework/views storage/logs bootstrap/cache

    # Режим разработки (bind-mount): vendor с хоста может отсутствовать.
    if [ ! -f vendor/autoload.php ]; then
        log "Устанавливаем PHP-зависимости (composer install)…"
        composer install --no-interaction --no-progress --prefer-dist
    fi
}

load_app_key() {
    if [ -n "$APP_KEY" ]; then
        return
    fi

    # Ключ хранится в томе storage, чтобы переживать пересоздание контейнера
    # и быть общим для app и queue.
    if [ ! -s "$KEY_FILE" ]; then
        php artisan key:generate --show --no-ansi > "$KEY_FILE"
        chmod 600 "$KEY_FILE"
        log "Сгенерирован APP_KEY"
    fi

    APP_KEY="$(cat "$KEY_FILE")"
    export APP_KEY
}

fix_permissions() {
    chown -R www-data:www-data storage bootstrap/cache
}

case "$ROLE" in
    app)
        prepare_env
        load_app_key

        php artisan app:wait-for-db --timeout=120
        php artisan migrate --force
        php artisan app:seed-if-empty

        if [ ! -e public/storage ]; then
            php artisan storage:link
        fi

        if [ "$APP_ENV" = "production" ]; then
            php artisan optimize
        else
            php artisan optimize:clear
        fi

        fix_permissions
        log "Готово: сайт доступен на http://localhost:${APP_PORT:-8000}"
        exec apache2-foreground
        ;;

    queue)
        prepare_env
        # Ключ создаёт контейнер app; queue стартует после его healthcheck.
        load_app_key
        php artisan app:wait-for-db --timeout=120
        fix_permissions
        log "Запускаем воркер очереди"
        exec runuser -u www-data -- php artisan queue:work --sleep=3 --tries=3 --max-time=3600
        ;;

    *)
        exec "$@"
        ;;
esac
