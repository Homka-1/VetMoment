# Развёртывание VetMoment на сервере

Инструкция для «боевого» запуска на сервере с Linux (Ubuntu 22.04/24.04 или любой другой, где есть Docker):
настоящий домен, HTTPS, своя база без демо-данных, письма через SMTP. Для показа на своём компьютере это
не нужно — там достаточно `start.bat` (см. README).

Используются те же образ и `docker-compose.yml`, что и при локальном запуске, плюс дополнение
`docker-compose.prod.yml` и файл настроек `deploy.env`.

## Что нужно

| Что | Зачем |
|-----|-------|
| Сервер: 2 ядра, 2 ГБ памяти, 20 ГБ диска | приложение, PostgreSQL, сборка образа (сборка — самый «тяжёлый» шаг) |
| Docker Engine 24+ и Docker Compose v2.24+ | `docker compose version`; v2.24 нужна для `!override` в `docker-compose.prod.yml` |
| Домен, DNS-запись `A` (и `AAAA`, если есть IPv6) на IP сервера | сертификат Let's Encrypt выдаётся на домен |
| Открытые порты 80 и 443 | HTTP→HTTPS и сам сайт; порт 8000 открывать **не нужно** |
| SMTP-ящик (логин, пароль, хост) | письма: сброс пароля, уведомления |

## Первый запуск

1. Получите код:

   ```bash
   git clone https://github.com/Homka-1/VetMoment.git
   ```

   ```bash
   cd VetMoment
   ```

2. Создайте файл настроек из примера и заполните его:

   ```bash
   cp deploy.env.example deploy.env
   ```

   | Переменная | Значение |
   |------------|----------|
   | `APP_DOMAIN` | домен без `https://`, например `vetmoment.kz` |
   | `DB_PASSWORD` | длинный случайный пароль базы, например вывод `openssl rand -hex 24` |
   | `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | данные SMTP (`smtps` и 465 — SSL; `smtp` и 587 — STARTTLS) |
   | `SEED_DEMO` | `false` — пустая база (по умолчанию); `true` — загрузить демо-данные для показа |
   | `PAYMENT_GATEWAY` | `fake` — демо-банк; `cloudpayments_kz` — после договора с провайдером (+ ключи ниже) |
   | `CLOUDPAYMENTS_PUBLIC_ID`, `CLOUDPAYMENTS_API_SECRET` | ключи из кабинета CloudPayments KZ |
   | `LOG_LEVEL` | `warning` (по умолчанию) или `info`/`debug` на время разбора проблем |

   `deploy.env` содержит пароли — он в `.gitignore` и не попадает ни в git, ни в Docker-образ.

3. Соберите и запустите (с HTTPS-прокси Caddy — профиль `https`):

   ```bash
   docker compose -f docker-compose.yml -f docker-compose.prod.yml --env-file deploy.env --profile https up -d --build --wait
   ```

   При первом старте контейнер `app` сам создаст `APP_KEY` (хранится в томе `vetmoment_storage`), выполнит миграции
   и подготовит кеши; Caddy получит сертификат за 10–60 секунд после того, как DNS указывает на сервер.

4. Создайте администратора (пароль спросит скрытым вводом):

   ```bash
   docker compose exec app php artisan app:create-admin admin --name="Администратор" --phone="+7 701 000 00 00" --email=admin@vetmoment.kz
   ```

5. Откройте `https://<APP_DOMAIN>/login`, войдите администратором. Приюты регистрируются сами («Стать приютом-партнёром»)
   и появляются в очереди модерации в `/admin/shelters`.

Чтобы не писать длинную команду каждый раз, можно задать переменные окружения в сессии:

```bash
export COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml COMPOSE_PROFILES=https COMPOSE_ENV_FILES=deploy.env
```

после этого работают короткие `docker compose up -d --build`, `docker compose logs`, `docker compose exec …`.
Ниже команды приведены в коротком виде.

## Чем серверный режим отличается от локального

| | Локально (`start.bat`) | Сервер (`docker-compose.prod.yml`) |
|---|---|---|
| Адрес | `http://localhost:8000` | `https://APP_DOMAIN` через Caddy; порт 8000 слушает только `127.0.0.1` |
| Демо-данные | загружаются в пустую базу | нет (`SEED_DEMO=false`) |
| Пароль БД | `vet_pass` | `DB_PASSWORD` из `deploy.env` |
| Письма | в журнал (`MAIL_MAILER=log`) | SMTP |
| Cookie | обычные | `Secure` (только HTTPS), HSTS на год |
| Режим | `APP_ENV=production`, `APP_DEBUG=false` | то же |

Остальное одинаково: `APP_ENV=production`, кеш конфигурации и маршрутов, CSP и прочие заголовки безопасности,
сервисы `queue` (письма) и `scheduler` (фоновые задачи).

## Обновление

```bash
git pull
```

```bash
docker compose up -d --build --wait
```

Миграции выполняются автоматически при старте `app`. Данные (база, загруженные фото, `APP_KEY`) лежат в томах
`vetmoment_pgdata` и `vetmoment_storage` и пересборку переживают. Короткий простой (секунды) — при перезапуске `app`.

## Резервные копии

База:

```bash
docker compose exec -T db pg_dump -U vet_user -d vetmoment -Fc > vetmoment-$(date +%F).dump
```

Загруженные файлы и `APP_KEY` (без ключа не расшифровать сессии; данные в базе не шифруются):

```bash
docker run --rm -v vetmoment_vetmoment_storage:/data -v "$PWD":/backup alpine tar czf /backup/storage-$(date +%F).tgz -C /data .
```

Восстановление базы в пустой том:

```bash
docker compose exec -T db pg_restore -U vet_user -d vetmoment --clean --if-exists < vetmoment-2026-11-23.dump
```

Делайте копии по расписанию (cron на сервере) и храните их не на том же сервере.

## Проверка после запуска

```bash
docker compose ps
```

все сервисы — `healthy`/`running`;

```bash
curl -fsS https://<APP_DOMAIN>/up
```

ответ 200; в браузере — замок HTTPS, `https://<APP_DOMAIN>/sitemap.xml` и `/robots.txt` содержат ваш домен,
в исходном коде страниц `og:url` — тоже. Письмо приходит при «Забыли пароль?».

Расписание задач:

```bash
docker compose exec app php artisan schedule:list
```

## Частые проблемы

| Симптом | Причина и решение |
|---------|-------------------|
| `required variable APP_DOMAIN is missing a value` | не передан `--env-file deploy.env` или переменная пуста |
| Caddy не получает сертификат | DNS ещё не указывает на сервер или закрыт порт 80/443: `docker compose logs caddy` |
| `password authentication failed for user "vet_user"` | `DB_PASSWORD` сменили после первого запуска: пароль задаётся только при создании тома. Верните старый или смените пароль в самой базе (`ALTER USER vet_user PASSWORD '…'`) |
| Ссылки в письмах ведут на `http://localhost` | письма отправлены до правки `deploy.env`; после изменения переменных — `docker compose up -d` (контейнеры пересоздадутся) |
| Ошибка 419 при отправке форм | сайт открыт не по `https://APP_DOMAIN` (например, по IP): cookie `Secure` не передаются по HTTP |
| Письма не уходят | `docker compose logs queue` — ошибки SMTP; проверьте `MAIL_*` |

## Свой веб-сервер вместо Caddy

Если на сервере уже есть nginx или другой прокси, запускайте без профиля `https` и проксируйте на `127.0.0.1:8000`
(порт задаётся `APP_PORT`), передавая заголовки `X-Forwarded-Proto`, `X-Forwarded-For`, `X-Forwarded-Host`:

```nginx
location / {
    proxy_pass http://127.0.0.1:8000;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Host $host;
    client_max_body_size 100m;
}
```

`TRUSTED_PROXIES` по умолчанию `*` — приложение доступно только с `127.0.0.1` и из сети Docker, поэтому это безопасно.
Если порт приложения открыт наружу, укажите адрес прокси явно.

## Настоящие платежи

Демо-банк (`PAYMENT_GATEWAY=fake`) не списывает деньги. Для приёма платежей нужен договор с провайдером. Драйвер
CloudPayments KZ (`app/Payments/CloudPaymentsGateway.php`) — заготовка, на реальном API не проверялась: перед запуском
пройдите тестовый режим провайдера. В кабинете CloudPayments укажите уведомления:
`https://<APP_DOMAIN>/payments/webhook/cloudpayments_kz?type=pay` (а также `type=fail` и `type=refund`).
