# Этап 4. Чистая инициализация проекта и окружения

Дата: 2026-11-04 · Статус: ✅ готов (сборка образа и PostgreSQL подтверждены в GitHub Actions;
`start.bat` на Windows ещё не запускался — см. «Как проверялось»)

## Цель этапа
Чистая техническая основа под новую тему: без старого домена, с запуском на Windows одним
`start.bat` / `docker compose up -d --build`, русской локалью, тестами, линтером и CI.

## Что сделано
- **Удалён старый домен** «срочных запросов»: `AnimalRequest`, `AnimalRequestController`, `RequestAccepted`,
  миграция `animal_requests`, все старые Blade-шаблоны (`layouts/app`, `home`, `auth/*`, `profile/*`,
  `notifications/*`, `welcome`), а также `AuthController`, `ProfileController`, `NotificationController`
  и их маршруты. Главная `/` — техническая заглушка (`resources/views/home.blade.php`).
- **Телефоны**: `App\Support\Phone` (`normalize`, `isValid`, `format`) — только казахстанские мобильные
  `+77XXXXXXXXX`; ввод `8 7XX…`, `7 7XX…`, `+7 (7XX)…`, `7XX…` (10 цифр), с пробелами/скобками/дефисами/точками.
  Правило валидации `App\Rules\KazakhPhone` с сообщением `validation.kz_phone`.
- **Фабрика и сидер** исправлены под реальную таблицу `users` (`login`, `phone`, `password`);
  сидер создаёт пользователя `demo` / `password`.
- **Локаль и время**: `APP_LOCALE=ru`, `faker_locale=ru_RU`, `APP_NAME=VetMoment`; `app.timezone` = UTC,
  новый ключ `app.display_timezone` = `Asia/Almaty`. Русские переводы `lang/ru/{validation,auth,passwords,pagination}.php`
  (все ключи `validation` фреймворка + `attributes` с названиями полей).
- **`.env.example`** — для Docker (pgsql, `DB_HOST=db`, `APP_PORT=8000`, production); **`.env.sqlite.example`** — для Herd/SQLite.
- **Docker**:
  - `Dockerfile` — 3 стадии: `composer:2` (vendor без dev) → `node:22-alpine` (`npm ci && npm run build`) →
    `php:8.4-apache` (`pdo_pgsql`, `zip`, mod_rewrite, production `php.ini`, `HEALTHCHECK` на `/up`).
  - `docker-compose.yml` — режим запуска: `app` (:`${APP_PORT:-8000}`), `queue` (воркер, стартует после
    healthy `app`), `db` (PostgreSQL 15, порт наружу **не** открыт). Без bind-mount; тома `vetmoment_pgdata`
    и `vetmoment_storage`. Логи — в `docker compose logs` (`LOG_CHANNEL=stderr`).
  - `docker-compose.dev.yml` — bind-mount кода, `vite` (HMR :5173, `node_modules` в томе), `mailpit` (:8025),
    порт БД `${DB_PORT:-5432}`, `APP_ENV=local`.
  - `docker/start.sh` (перенесён из корня) — роли `app` / `queue`: `.env` из примера, `APP_KEY` генерируется
    один раз и хранится в томе (`storage/app/.app_key`), ожидание БД (`app:wait-for-db`), `migrate --force`,
    демо-данные только при пустой базе (`app:seed-if-empty`), `storage:link`, `optimize` в production,
    права `www-data`; в dev — `composer install`, если нет `vendor/`.
  - `.dockerignore` (vendor, node_modules, .env, public/build, design, docs, …).
- **Windows**: `start.bat` (проверяет Docker, пробует сам запустить Docker Desktop, `docker compose up -d --build`,
  ждёт healthy, открывает браузер; сообщения по-русски) и `stop.bat`. В `.gitattributes` правила
  `*.sh eol=lf`, `*.bat eol=crlf` уже были — проверено `git ls-files --eol`.
- **Качество**: `pint.json` (preset laravel), 29 тестов (unit `Phone`, feature: главная/`/up`/404/локаль,
  русские сообщения валидации и `KazakhPhone`, фабрика, `app:seed-if-empty`, `app:wait-for-db`);
  `tests/TestCase` отключает Vite (`withoutVite`), в `phpunit.xml` — тестовый `APP_KEY`.
  GitHub Actions `.github/workflows/ci.yml`: Pint; тесты на SQLite и PostgreSQL 15 + миграции и сид с нуля;
  `npm run build`; `docker compose up -d --build --wait` + проверка `http://localhost:8000`.
- **README** переписан: «Запуск на Windows за 3 шага» → запасной способ (Herd + SQLite) → режим разработки.

## Изменённые / созданные файлы
| Файл | Что |
|------|-----|
| `app/Support/Phone.php`, `app/Rules/KazakhPhone.php` | телефоны КЗ |
| `app/Models/User.php` | без связи со старым доменом |
| `database/factories/UserFactory.php`, `database/seeders/DatabaseSeeder.php` | исправлены |
| `routes/web.php`, `routes/console.php` | заглушка главной; команды `app:wait-for-db`, `app:seed-if-empty` |
| `resources/views/home.blade.php`, `resources/css/app.css` | техническая заглушка |
| `config/app.php`, `lang/ru/*` | локаль, часовые пояса, переводы |
| `.env.example`, `.env.sqlite.example` | окружения Docker / SQLite |
| `Dockerfile`, `docker-compose.yml`, `docker-compose.dev.yml`, `docker/start.sh`, `.dockerignore` | Docker |
| `start.bat`, `stop.bat` | запуск на Windows |
| `composer.json`, `composer.lock` | имя/описание; `fakerphp/faker` перенесён в `require` |
| `pint.json`, `phpunit.xml`, `tests/**`, `.github/workflows/ci.yml` | качество и CI |
| `README.md`, `CLAUDE.md` | инструкции запуска; путь `docker/start.sh` |
| удалены | старый домен, контроллеры, шаблоны, примеры тестов, корневой `start.sh` |

## Принятые решения
- **Удалены и контроллеры входа/профиля/уведомлений**, а не только `AnimalRequest`: они завязаны на удалённые
  шаблоны, а аутентификация проектируется заново на этапе 6. Миграция `notifications` оставлена (механизм
  database-уведомлений переиспользуем на этапе 12).
- **`fakerphp/faker` — в `require`**, а не в `require-dev`: в образе нет dev-пакетов, а демо-данные создаются
  при первом запуске через фабрики. Без этого `db:seed` в контейнере падал (поймано при проверке).
- **`APP_KEY` хранится в томе `storage`**, а не в `.env` контейнера: переживает пересоздание контейнера и
  общий для `app` и `queue` (иначе очередь не расшифрует задания и сбросятся сессии).
- **Демо-данные — только при пустой базе** (`app:seed-if-empty` проверяет таблицу `users`): повторный
  `start.bat` не дублирует данные и не затирает изменения пользователя.
- **Режим запуска — `APP_ENV=production`, `APP_DEBUG=false`** (можно включить `APP_DEBUG=true` переменной);
  в dev — `local`/`true`.
- **Время**: `app.timezone` остаётся UTC (в БД — UTC), для показа — `config('app.display_timezone')`.
  Хелперы форматирования дат/сумм — на этапе 7.
- **Fallback-локаль — `en`**, чтобы непереведённые строки фреймворка не превращались в ключи.
- **Точка входа — через `sh`** (`ENTRYPOINT ["sh", ".../start.sh"]`), а в образе `sed` убирает CR: скрипт не
  зависит от бита исполнения и CRLF при bind-mount с Windows.
- `start.bat` пробует сам запустить Docker Desktop из стандартного пути и ждёт до 3 минут.

## Отклонения от плана
- `start.sh` перенесён в `docker/start.sh` (рядом с Docker-файлами; в корне — только то, что нужно пользователю).
- Удалены контроллеры аутентификации/профиля/уведомлений (в плане — только старый домен), см. «Принятые решения».
- Добавлены служебные artisan-команды `app:wait-for-db` и `app:seed-if-empty` (в `routes/console.php`).
- Скилл `frontend-design` не вызывался: UI на этапе не делался, главная — техническая заглушка без дизайна.

## Как проверялось
В окружении агента локальный PHP — 8.3 (lock требует 8.4), поэтому всё гонялось в контейнере `php:8.4-apache`.
Сеть окружения пропускает Docker Hub/npm/Composer только через TLS-прокси, а Debian-зеркала (`deb.debian.org`)
заблокированы политикой — поэтому:

| Проверка | Результат |
|----------|-----------|
| `php artisan test` (SQLite in-memory) | ✅ 29 тестов, 62 проверки |
| `./vendor/bin/pint --test` | ✅ |
| `npm run build` | ✅ |
| `docker compose up -d --build --wait` по оригинальному `Dockerfile` с PostgreSQL | ✅ в GitHub Actions (локально стадия `apt-get` не скачивает пакеты из-за сети окружения). Первый прогон CI выявил, что `--wait` падает на `queue` без healthcheck — добавлена проверка процесса `queue:work` |
| Полный сценарий запуска на образе без `apt`-шага (vendor с хоста), БД — SQLite | ✅ `.env` и `APP_KEY` создаются сами, миграции, сид, `storage:link`, Apache, healthcheck `healthy`, `/` → 200, ассеты из `public/build` → 200, 404 работает; `queue` выполняет задания; после `down`/`up` ключ сохранился, сид не повторился |
| Режим разработки (`docker-compose.dev.yml`) | ✅ bind-mount, Vite HMR (`public/hot`, `@vite/client` → 200), `php artisan test` в контейнере; ⚠️ `npm ci` внутри контейнера и `mailpit` не проверены (сеть/лимит Docker Hub) |
| Запасной путь `.env.sqlite.example` (key:generate, migrate --seed, storage:link, serve) | ✅ |
| Тесты и миграции на PostgreSQL 15 | ✅ в GitHub Actions |
| `start.bat` / `stop.bat` | ⚠️ не проверено (нет Windows); CRLF в рабочей копии подтверждён |

Команды для повторной проверки (на машине с Docker):
```bash
docker compose up -d --build --wait
```
```bash
docker compose exec app php artisan app:seed-if-empty
```
Разработка:
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```
```bash
docker compose exec app php artisan test
```

## Известные проблемы и долги
- Нужно подтвердить на реальной машине: `start.bat` на Windows 10/11.
- `php artisan test` **без `.env`** выводит безвредные предупреждения `file_get_contents(.env)` (Collision
  показывает подавленное предупреждение phpdotenv). С `.env` (как в Docker и CI) их нет.
- `mailpit` — тег `latest` (только dev).
- Пользователь `demo` пока без роли и без страницы входа — появятся на этапах 5–6.

## Что важно знать на следующем этапе
- Этап 5: таблица `users` пока `login`, `phone`, `password`; добавить `name`, `email`, `role` и т.д. —
  правкой исходной миграции (данных в проде нет, «с нуля»). Фабрики должны давать валидные телефоны
  (`Phone::isValid`) — на это есть тест.
- Демо-данные создаются при первом запуске контейнера командой `app:seed-if-empty`, признак пустой базы —
  отсутствие строк в `users`. Сидер должен быть **идемпотентно безопасным** для `migrate:fresh --seed` и не
  требовать dev-пакетов (Faker уже в `require`). Демо-логины — обновить в README.
- Фото для сидера: в образ не попадают `design/` и `docs/` (`.dockerignore`) — исходники фото класть в
  `database/seeders/` или `resources/`, копировать в `storage/app/public` при сиде.
- Новые Form Requests: телефон нормализовать в `prepareForValidation()` через `Phone::normalize()`, затем
  `KazakhPhone` + `unique`.
- Конфиги бизнес-правил (`config/walks.php` и т.п.) — значения из `env()` с дефолтами; если нужна новая
  переменная окружения для Docker — добавить в `x-app-env` в `docker-compose.yml` и в оба `.env*.example`.
