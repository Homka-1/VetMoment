# Архитектура VetMoment

Сервис помощи бездомным животным и приютам Казахстана: анкеты питомцев, сбор пожертвований, запись на выгул.
Монолит на Laravel 13 (PHP 8.4) с серверным рендерингом Blade; JavaScript только улучшает страницы
(все формы работают и без него). Модель данных — [`er.md`](er.md), продуктовые требования — [`docs/product/`](../product/),
дизайн — [`docs/design/`](../design/).

## Слои

```
HTTP-запрос
  └─ middleware: web (сессия, CSRF, EnsureUserIsNotBlocked), role:…, throttle:…, SecurityHeaders (глобально)
      └─ контроллер (тонкий) ─ Form Request (вся валидация и нормализация ввода)
            │                  Gate / Policy (права на объект)
            ├─ сервис app/Services (бизнес-правила, транзакции, блокировки, уведомления)
            │     └─ модели Eloquent + enum-статусы
            └─ Blade-шаблон ← компоненты UI-кита resources/views/components
```

| Каталог | Что лежит | Правило |
|---------|-----------|---------|
| `app/Http/Controllers/{Pets,Campaigns,Donations,Walks,Public}` | публичные разделы | без бизнес-логики: принять запрос → сервис → ответ |
| `app/Http/Controllers/Cabinet` | кабинет волонтёра (`/cabinet`) | |
| `app/Http/Controllers/Shelter` | кабинет приюта (`/shelter`, `role:shelter_staff`) | свой приют — `$user->shelter()` |
| `app/Http/Controllers/Admin` | админка (`/admin`, `role:admin`) | |
| `app/Http/Controllers/Payments` | демо-банк и webhook провайдера | webhook — без CSRF, с подписью |
| `app/Http/Requests` | Form Requests | телефон → `Phone::normalize()` в `prepareForValidation()` |
| `app/Policies` | права на объекты | «своё» = `shelter_id` совпадает с приютом пользователя |
| `app/Services` | бизнес-логика | изменения статусов — по таблицам переходов `TRANSITIONS` |
| `app/Enums` | статусы и роли (backed enums) | подпись `label()`, тон бейджа `tone()` |
| `app/Support` | утилиты | деньги `Money`, время `LocalTime`, телефон `Phone`, склонения `Plural`, SEO `Seo`, меню `Navigation` |
| `app/Notifications` | уведомления (база + письмо) | наследники `AppNotification` |
| `app/Payments` | платёжный слой | интерфейс `PaymentGateway`, драйверы `fake`, `cloudpayments_kz` |

## Предметные модули

**Питомцы.** `Pet` (+ `PetPhoto`) принадлежит `Shelter`. Каталог — `PetCatalog` (фильтры, поиск без учёта регистра
переносимым `LOWER(...) LIKE`), видимость — скоупы `Pet::visible()` / `inCatalog()`. Анкеты в кабинете приюта —
`PetEditor`, фото — `PetPhotoStorage` (проверка содержимого, ресайз до 1600 px и превью 600 px в WebP через GD).
Заявки «Хочу забрать / Взять под опеку» — `AdoptionApplications` (переходы статусов, автозакрытие остальных заявок,
когда питомец нашёл дом).

**Пожертвования.** `DonationCampaign` (сбор, смета, отчёты `CampaignReport` + `ReportAttachment`) и `Donation`.
Деньги — целые тиыны (`unsignedBigInteger`), вывод `Money::format()` → `1 500 ₸`.

```
форма → Donation(pending, idempotency_key) → PaymentGateway::start() → страница банка
      → webhook / демо-банк → DonationPayments::apply() [транзакция, lockForUpdate на сбор]
      → succeeded → DonationCampaign::recalculateTotals() → syncCompletion() → «Цель достигнута»
```

Повторный webhook идемпотентен (переходы по `DonationPayments::TRANSITIONS`), сумма сбора пересчитывается агрегатом
только по оплаченным. Неоплаченные платежи истекают (`app:expire-pending-donations`), сборы закрываются по сроку
(`app:close-expired-campaigns`).

**Выгул.** Приют задаёт шаблон недели (`WalkTemplate`) и/или отдельные слоты (`WalkSlot`, `WalkSlots`). Запись —
`WalkBookingService::book()`: правила W1–W10 (горизонт 14 дней, запись не позже чем за 2 ч, не больше 3 активных записей,
блок после 3 неявок за 90 дней и т.д., `config/walks.php`); гонки исключены `lockForUpdate()` на слот и пересчётом
`booked_count`. Приют подтверждает / отклоняет / отмечает «Состоялся» и «Не пришёл»; неподтверждённые записи
отменяются системой к началу прогулки, за сутки уходит напоминание.

**Приюты и модерация.** Волонтёр подаёт заявку на приют (`ShelterRegistration`), админ одобряет / отклоняет
(`ShelterModeration`); до одобрения кабинет приюта работает в режиме черновика, публичные страницы скрыты.
Блокировка пользователя (`UserModeration`) запрещает вход и отменяет его записи на выгул.

**Уведомления.** 9 типов (`app/Notifications`): каналы `database` (колокольчик и `/notifications`) и `mail`
(фирменный шаблон `resources/views/vendor/mail`). Отправка — через очередь (`ShouldQueue`); получатели «от приюта» —
`Recipients::shelterStaff()`.

## Роли и доступ

| Роль | Где работает | Как проверяется |
|------|--------------|-----------------|
| гость | публичные страницы, пожертвование без входа | — |
| волонтёр (`volunteer`) | `/cabinet/*`, заявки, запись на выгул, избранное | `auth` + политики |
| сотрудник приюта (`shelter_staff`) | `/shelter/*` своего приюта (владелец / сотрудник) | `role:shelter_staff` + политики по `shelter_id` |
| администратор (`admin`) | `/admin/*`, модерация | `role:admin` |

Подробно — [`docs/product/roles.md`](../product/roles.md).

## Время, деньги, телефоны

- В БД время — UTC, показывается в `Asia/Almaty` (`config('app.display_timezone')`, класс `LocalTime`).
- Суммы — тиыны (1 ₸ = 100 тиын), ввод `Money::fromTenge()`, вывод `Money::format()`.
- Телефоны — казахстанские мобильные в E.164 (`+77XXXXXXXXX`), правило `KazakhPhone`.
- Код не зависит от СУБД: никаких pgsql-специфичных типов и операторов; тесты идут на SQLite и PostgreSQL 15.

## Фронтенд

Tailwind CSS 4: дизайн-токены — в `@theme` (`resources/css/app.css`, источник — `design/tokens.json`), UI-кит —
Blade-компоненты `resources/views/components` (витрина `/styleguide` в режиме разработки). JS — модули
`resources/js/ui/*` без фреймворка (модальные окна, тосты, календарь выгула, менеджер фото, автоотправка фильтров).
Шрифты и иконки — локальные, внешних запросов нет (это же проверяет smoke-тест и политика CSP).
Сборка — Vite, в Docker-образе ассеты собираются на этапе сборки.

## Фоновые задачи

| Процесс | Что делает |
|---------|-----------|
| `queue` (`queue:work`, драйвер `database`) | письма и уведомления |
| `scheduler` (`schedule:work`, `routes/console.php`) | истечение платежей (10 мин), закрытие сборов, напоминания о выгуле, отмена неподтверждённых записей (15 мин), чистка упавших задач (сутки) |

Без Docker (Herd): `QUEUE_CONNECTION=sync`, планировщик — `php artisan schedule:work` в отдельном окне.

## Развёртывание

```
            ┌───────────── docker compose (сеть vetmoment) ─────────────┐
браузер ──► │ caddy :80/:443 (только на сервере, профиль https)          │
            │   └─► app :80  Apache + PHP 8.4, миграции и демо при старте │
            │       queue      — тот же образ, queue:work                 │
            │       scheduler  — тот же образ, schedule:work              │
            │       db         — PostgreSQL 15 (наружу не открыт)          │
            │ тома: vetmoment_pgdata (база), vetmoment_storage (файлы, APP_KEY) │
            └────────────────────────────────────────────────────────────┘
```

| Режим | Команда | Особенности |
|-------|---------|-------------|
| локальный запуск (Windows) | `start.bat` / `docker compose up -d --build` | демо-данные, http://localhost:8000 |
| разработка | `+ docker-compose.dev.yml` | bind-mount кода, Vite HMR, Mailpit, порт БД |
| сервер | `+ docker-compose.prod.yml --env-file deploy.env --profile https` | домен и HTTPS (Caddy), без демо-данных, почта по SMTP — [`docs/deploy.md`](../deploy.md) |
| без Docker | Laravel Herd + SQLite | `.env.sqlite.example`, README |

Точка входа контейнера — `docker/start.sh` (роли `app`, `queue`, `scheduler`): `.env` из примера, `APP_KEY` в томе
`storage`, ожидание БД, `migrate --force`, демо-данные при пустой базе (`SEED_DEMO`), `storage:link`, `optimize`.

## Безопасность

- CSRF — во всех формах (исключение — webhook провайдера, подлинность по HMAC-подписи).
- XSS — вывод только через `{{ }}`; `{!! !!}` — лишь для статичного спрайта иконок и XML-пролога sitemap.
- Загрузки — правило `image` + `mimes:jpg,jpeg,png,webp` (тип по содержимому), размеры 2–8 МБ, имя файла
  генерируется; Apache запрещает выполнение PHP в `/storage` и отдаёт файлы с `nosniff` и `sandbox`-CSP.
- Права — политики на каждый объект + middleware ролей; заблокированный пользователь разлогинивается.
- Лимиты частоты: вход 10/мин, регистрация 10/ч, сброс пароля 3/мин, формы 10/мин, webhook 120/мин.
- Заголовки (`SecurityHeaders`): `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
  `Permissions-Policy`, CSP в production, HSTS на HTTPS; за прокси — `TRUSTED_PROXIES`.
- Секретов в репозитории нет: `APP_KEY` генерируется при первом старте, платёжный шлюз по умолчанию — демо.

## Тестирование

| Уровень | Где | Запуск |
|---------|-----|--------|
| Unit | `tests/Unit` (enum-ы, деньги, телефоны, склонения, правила) | `php artisan test` |
| Feature | `tests/Feature` — сценарии по разделам, политики, уведомления, SEO, безопасность, дымовой обход демо-данных | `php artisan test` (SQLite in-memory; CI — ещё PostgreSQL) |
| Браузер | `tests/browser/smoke.mjs` — обход 45 страниц под 4 ролями + сквозные сценарии | `node tests/browser/smoke.mjs` на работающем сайте |
| CI | `.github/workflows/ci.yml` | Pint, тесты SQLite и PostgreSQL, сборка фронтенда, сборка образа и `docker compose up --wait` |
