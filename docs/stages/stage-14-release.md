# Этап 14. Тестирование, документация, подготовка к деплою

Дата: 2026-11-23 · Статус: ⚠️ готов с оговорками — запуск на настоящей Windows не проверен (нет машины), нужен чек-лист
[`docs/windows-run.md`](../windows-run.md) на ПК пользователя

## Цель этапа
Покрыть ключевые сценарии тестами (включая браузер), проверить безопасность, принять запуск «Windows + Docker Desktop»
и «Herd + SQLite», подготовить prod-сборку и инструкцию по деплою, дописать README и обзор архитектуры.

## Что сделано
- **Зависимости**: `composer update` — `composer audit` с 48 уязвимостей до **0** (`laravel/framework` 13.7 → 13.43,
  `guzzlehttp/guzzle` 7 → 8, `symfony/*` 8.0 → 8.1, `league/commonmark`, `league/flysystem`). Все тесты прошли без правок кода.
- **Впервые в окружении агента собран Docker-образ и поднят `docker compose`** (на прошлых этапах мешал прокси песочницы;
  обход — временный Dockerfile вне репозитория с CA прокси и заранее скачанным `vendor`). Проверено: первый старт с пустыми
  томами за 18 с после сборки, повторный старт без повторного сида, production-кеши, серверный режим.
- **Тесты на PostgreSQL 15** впервые прогнаны локально (в собранном образе есть `pdo_pgsql` и GD): 433/433, без пропусков.
- **Безопасность** (аудит + исправления):
  - middleware `SecurityHeaders` (глобально, в том числе для 404 неизвестного адреса): `X-Content-Type-Options`,
    `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`; **Content-Security-Policy** в production
    (`script-src 'self'`, без inline-скриптов — проверено обходом браузера, нарушений нет); HSTS на HTTPS. Настройки —
    `config/security.php` (`SECURITY_CSP`, `SECURITY_HSTS_MAX_AGE`, `TRUSTED_PROXIES`);
  - Apache: `ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`; для `/storage/*` — `nosniff`, `sandbox`-CSP и
    запрет выполнения `.php` (проверено: подложенный `evil.php` → 403);
  - лимит `throttle:webhooks` (120/мин с IP) на webhook платёжного провайдера;
  - `TrustProxies` по `TRUSTED_PROXIES` — за HTTPS-прокси ссылки, `Secure`-cookie и HSTS видят `https`;
  - проверено без изменений: CSRF во всех формах (кроме webhook с HMAC-подписью), вывод только через `{{ }}`
    (`{!! !!}` — лишь спрайт иконок и XML-пролог), загрузки — `image` + `mimes` по содержимому, имена файлов генерируются,
    лимиты на вход/регистрацию/сброс пароля/формы, политики на каждом объекте.
- **Тесты**: +8 (`tests/Feature/Security/SecurityTest.php`, всего 433): заголовки и CSP, HSTS, настоящий CSRF (419 без токена,
  webhook — без 419), экранирование `<script>` в кличке и описании, загрузка PHP-скрипта под видом JPG, SVG со скриптом,
  HTML под видом PNG (тип — по содержимому, через настоящий файл, а не `UploadedFile::fake()`, который берёт тип из имени),
  лимит webhook, команда создания администратора.
- **Браузерная smoke-проверка** `tests/browser/smoke.mjs` (Playwright): обход 45 страниц под 4 ролями — коды ответов,
  ошибки консоли и CSP, внешние запросы, горизонтальная прокрутка на 360 и 1440; сквозные сценарии: пожертвование гостем
  через демо-банк, заявка «Хочу забрать», запись на выгул → подтверждение приютом → уведомление волонтёру → отмена записи.
  Повторяемый (на той же базе выбирает следующего питомца/слот, отменяет свою запись), каждый пользователь входит один раз.
  Добавлен в CI (job «Docker-образ и запуск»). Прогнан на Docker (production, CSP), на Herd-копии (SQLite, `sync`-очередь)
  и в режиме разработки (письма в Mailpit) — 0 ошибок.
- **Деплой**: `docker-compose.prod.yml` (домен и HTTPS через Caddy с Let's Encrypt — профиль `https`, пароль БД, SMTP,
  `Secure`-cookie, без демо-данных, порт приложения только на `127.0.0.1`), `docker/Caddyfile` (проверен `caddy validate`),
  `deploy.env.example`, переменная `SEED_DEMO` в `start.sh`, команда `php artisan app:create-admin`. Инструкция —
  [`docs/deploy.md`](../deploy.md) (первый запуск, обновление, резервные копии — команды проверены, nginx вместо Caddy, частые проблемы).
- **Приёмка Windows** — [`docs/windows-run.md`](../windows-run.md): что проверено, 4 найденные проблемы (исправлены), чек-лист A1–A10 / B1–B4.
- **Документация**: [`docs/architecture/overview.md`](../architecture/overview.md) (слои, модули, роли, фоновые задачи,
  развёртывание, безопасность, тесты); README — ссылки на документы, разделы «Тесты и проверки», «Запуск на сервере»,
  «Безопасность», исправлена команда Herd, демо-аккаунты (были с этапа 5–6, сверены с сидером).

## Изменённые / созданные файлы
| Файл | Что |
|------|-----|
| `composer.lock` | обновление зависимостей по `composer audit` |
| `app/Http/Middleware/SecurityHeaders.php`, `config/security.php`, `bootstrap/app.php` | заголовки безопасности, CSP, HSTS |
| `app/Providers/AppServiceProvider.php` | `TrustProxies` по `TRUSTED_PROXIES`, лимит `webhooks` |
| `routes/web.php` | `throttle:webhooks` на webhook |
| `routes/console.php` | `app:create-admin` |
| `config/filesystems.php` | относительный адрес диска `public` (`/storage`) |
| `Dockerfile` | безопасность Apache, `/storage` без PHP |
| `docker-compose.yml` | `APP_ENV: production` без подстановки, `SEED_DEMO` |
| `docker/start.sh` | демо-данные только при `SEED_DEMO=true` |
| `docker-compose.prod.yml`, `docker/Caddyfile`, `deploy.env.example`, `.gitignore`, `.dockerignore` | серверный режим |
| `.github/workflows/ci.yml` | smoke-проверка браузером после `docker compose up` |
| `tests/Feature/Security/SecurityTest.php`, `tests/browser/smoke.mjs` | тесты |
| `docs/architecture/overview.md`, `docs/deploy.md`, `docs/windows-run.md`, `README.md` | документация |
| `docs/screenshots/stage-14/` | скриншоты 1440 и 390 (главная, каталог, сборы, выгул, кабинеты, админка) из production-контейнера |

## Принятые решения
- **`APP_ENV: production` в `docker-compose.yml` — без `${…}`**: Compose подставляет переменные из `.env` в папке проекта, а
  его создаёт запасной способ (Herd) с `APP_ENV=local`. Режим разработки по-прежнему задаёт `docker-compose.dev.yml`.
- **Серверные настройки — отдельным файлом и `--env-file deploy.env`**, а не через `.env`: тот же `.env` читает Laravel
  при запуске без Docker; общий файл для двух назначений и дал проблему выше. Локальный `docker-compose.yml` остался
  полностью «зашитым» — у пользователя на Windows нечего настраивать.
- **Caddy в compose под профилем** — HTTPS одной командой без отдельной установки nginx и certbot; кто уже держит свой
  прокси, запускает без профиля (пример nginx в `docs/deploy.md`).
- **Демо-данные на сервере выключены по умолчанию** (`SEED_DEMO=false`): у демо-пользователей пароль `password`;
  первый администратор — `app:create-admin` (пароль скрытым вводом или `ADMIN_PASSWORD`).
- **CSP только в production**: в разработке Vite dev server (:5173) и `onclick` в витрине UI-кита. `form-action` не
  ограничен — платёжный провайдер перенаправляет на свою страницу оплаты. `style-src 'unsafe-inline'` — для ширины
  полос прогресса (`style="width: 68%"`).
- **Относительный `/storage`**: фото не ломаются при открытии по `127.0.0.1`, по IP в локальной сети или на другом
  порту (`artisan serve --port`); абсолютный адрес нужен только Open Graph, его строит `Seo::absolute()`.
- **Playwright не добавлен в `package.json`**: иначе `npm ci` в образе тянул бы лишний пакет; скрипт рассчитан на
  `npm i --no-save playwright`. axe-core в скрипт не включён (нужен ещё один пакет) — доступность проверена на этапе 13,
  разметка с тех пор не менялась.
- **Обновление с мажорными версиями транзитивных пакетов** (Guzzle 8, brick/math 1.0) — так разрешил Composer в рамках
  ограничений Laravel 13; тесты на обеих СУБД и браузерный обход прошли.

## Отклонения от плана
- Приёмка на реальной Windows не выполнена (нет машины) — по плану проверено всё, что можно в Linux, и подготовлен
  чек-лист для пользователя (`docs/windows-run.md`).
- Сверх плана: серверный режим с HTTPS (Caddy) и командой создания администратора, обновление зависимостей (долг этапов 12–13),
  smoke-проверка в CI, относительные адреса фото.

## Как проверить
```bash
docker compose up -d --build --wait
```
```bash
node tests/browser/smoke.mjs
```
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec app php artisan test
```

| Проверка | Результат |
|----------|-----------|
| `phpunit` в `php:8.4-cli` (SQLite in-memory) | ✅ 433 теста, 1 пропущен (нет GD в `php:8.4-cli`) |
| `phpunit` в собранном образе против `postgres:15-alpine` | ✅ 433/433, без пропусков |
| `./vendor/bin/pint --test` | ✅ |
| `npm run build` | ✅ |
| `composer audit` | ✅ 0 уязвимостей |
| `docker build` + `docker compose up --wait` с пустыми томами | ✅ все сервисы `healthy`, демо-данные загружены |
| `tests/browser/smoke.mjs` — Docker (production, CSP) / Herd + SQLite / режим разработки | ✅ 0 ошибок во всех трёх |
| Письма в Mailpit (режим разработки) | ✅ пожертвование, новая запись, подтверждение, отмена |
| Серверный режим (`docker-compose.prod.yml`, без Caddy): без демо-данных, `app:create-admin`, `Secure`-cookie и HSTS за прокси, sitemap с доменом | ✅ |
| `caddy validate` для `docker/Caddyfile` | ✅ (получение сертификата не проверялось — нет домена) |
| Резервная копия: `pg_dump` и архив тома `storage` | ✅ |
| CRLF у `.bat` в рабочей копии и в `git archive` (ZIP GitHub), LF у `.sh` | ✅ |
| `start.bat` на Windows 10/11 | ⚠️ не проверено — чек-лист `docs/windows-run.md` |

## Известные проблемы и долги
- **Нужна проверка на Windows** по чек-листу `docs/windows-run.md` (сценарии A и B).
- Драйвер CloudPayments KZ — заготовка, на реальном API не проверялась (с этапа 9).
- Ссылки в письмах, sitemap и `og:url` строятся от `APP_URL`; при открытии сайта по IP из локальной сети они ведут на `localhost`.
- smoke-проверку нельзя запускать чаще раза в минуту (лимит входа 10/мин с IP).
- Сервис `vite` режима разработки в окружении агента не запускался (`npm ci` из контейнера блокирует прокси песочницы).
- Старые уведомления не чистятся (с этапа 12).
- `LoginTest::test_logout_requires_post` по-прежнему падает только на PHP 8.3 (проект требует 8.4).

## Что важно знать на следующем этапе
- Этап 15 (демо-макет): за основу статики удобно брать HTML из работающего контейнера (`docker compose up`, демо-данные) —
  страницы уже без внешних запросов (проверено smoke-тестом), стили — `public/build/assets/app-*.css` из `npm run build`.
  Учесть `file://`: адреса фото сейчас относительные от корня (`/storage/...`) — в макете их нужно переписать на
  относительные пути без ведущего `/`; шрифты подключаются из CSS сборки (`/build/assets/*.woff2`).
- Список страниц и ролей для макета — `ROLES` в `tests/browser/smoke.mjs` (то, что реально открывается под каждой ролью).
- В режиме запуска включён CSP `script-src 'self'`: если макет будет собираться из отрендеренных страниц, inline-скриптов
  в шаблонах по-прежнему нет — данные для статики (`window.DEMO_DATA`) подключаются отдельным файлом.
- Новые переменные окружения: `SEED_DEMO`, `TRUSTED_PROXIES`, `SECURITY_CSP`, `SECURITY_HSTS_MAX_AGE`, `PUBLIC_STORAGE_URL`,
  `ADMIN_PASSWORD` (только для `app:create-admin`).
