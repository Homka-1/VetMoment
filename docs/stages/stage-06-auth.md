# Этап 6. Аутентификация, роли, политики доступа

Дата: 2026-11-04 · Статус: ✅ готов

## Цель этапа
Вход/регистрация/восстановление пароля, регистрация приюта с модерацией, middleware ролей и Policies
по матрице прав `docs/product/roles.md`, валидация в Form Requests, rate limiting, тесты на права.

## Что сделано
- **Вход** (`/login`) по логину **или** телефону + пароль, «Запомнить меня», выход (`POST /logout`).
  `LoginRequest::authenticate()`: телефон в любом виде (`8 701…`, `+7 (701)…`) нормализуется `Phone::normalize()`,
  иначе поиск по логину без учёта регистра. Ошибка одинаковая для неизвестного логина и неверного пароля.
  Заблокированный (`is_blocked`) с верным паролем получает понятное сообщение «Аккаунт заблокирован».
  После входа — `intended` URL или кабинет по роли (`User::homeRoute()`: admin → `/admin`,
  shelter_staff → `/shelter`, volunteer → `/cabinet`); вошедший на `/login` уводится в кабинет.
- **Регистрация** (`/register`) — всегда роль `volunteer` (роль из запроса игнорируется); логин и почта
  приводятся к нижнему регистру, телефон — к E.164 до проверки `unique`; согласие на ПДн обязательно.
- **Восстановление пароля** (`/forgot-password`, `/reset-password/{token}`) — стандартный брокер Laravel;
  ответ одинаковый для существующей и несуществующей почты (нельзя проверить, зарегистрирован ли адрес).
  Письмо на русском (`lang/ru.json` — строки уведомления `ResetPassword` и шаблона писем).
- **Регистрация приюта** (`/cabinet/shelter-request`) — `ShelterApplicationRequest` + сервис
  `ShelterRegistration`: в транзакции приют `pending`, автор — `owner`, роль → `shelter_staff`; логотип
  (jpg/png/webp ≤ 2 МБ) на диск `public` (`shelters/logos`). Отклонённую заявку владелец исправляет той же
  формой → снова `pending`, причина отказа сбрасывается.
- **Модерация** — `ShelterModeration` + `Admin\ShelterModerationController`:
  `POST /admin/shelters/{slug}/approve | reject | block | unblock` (причина обязательна для reject/block,
  `ModerationReasonRequest`). Допустимость перехода — в `ShelterPolicy` (pending → approved/rejected,
  approved ⇄ blocked), недопустимый переход → 403. Экран очереди модерации — этап 11.
- **Middleware**: `role:<роль,...>` (`EnsureUserHasRole`; гость → вход, чужая роль → 403) и
  `EnsureUserIsNotBlocked` в группе `web` — заблокированный во время сессии выходит на следующем запросе.
- **Policies** (зарегистрированы явно в `AppServiceProvider`): `PetPolicy`, `CampaignPolicy` (для
  `DonationCampaign`), `WalkSlotPolicy`, `WalkBookingPolicy`, `AdoptionApplicationPolicy`, `ShelterPolicy`.
  «Своё» — `User::worksAt($shelter)`, владелец — `User::ownsShelter($shelter)`.
- **Rate limiting** (`AppServiceProvider`): `login` 10/мин на IP + в `LoginRequest` 5 неудачных попыток
  в минуту на «идентификатор + IP»; `register` 10/ч на IP; `password-reset` 3/мин на «почта + IP» и 20/ч на IP;
  `forms` 10/мин на пользователя/IP (применён к заявке приюта; на этапах 8–10 — к заявкам, бронированиям,
  пожертвованиям).
- **Правило `BinIin`** — 12 цифр + контрольный разряд (два набора весов); демо-БИН проходят проверку.
- **Заглушки кабинетов** `/cabinet`, `/shelter`, `/admin` (`CabinetStubController`, `cabinet/stub.blade.php`):
  кто вошёл, статус приюта (модерация / причина отказа), ссылка «Стать приютом-партнёром», выход.
- **Технические формы** входа, регистрации, восстановления пароля и заявки приюта
  (`layouts/stub.blade.php`): рабочие, с `label`, `autocomplete`, `role="alert"` для ошибок, но без дизайна.
- **Тесты**: +93 (всего 167, 1005 проверок) — вход (логин/телефон в разных форматах, блокировка, remember,
  троттлинг), регистрация (нормализация, дубли, российский номер, формат логина, пароль, согласие, лимит),
  сброс пароля (письмо по-русски, токен, лимит), доступ к кабинетам по ролям, блокировка во время сессии,
  заявка приюта и повторная отправка, модерация и недопустимые переходы, матрица всех 6 политик, `BinIin`.

## Изменённые / созданные файлы
| Файл | Что |
|------|-----|
| `app/Http/Controllers/Auth/*` | вход/выход, регистрация, сброс пароля |
| `app/Http/Controllers/Cabinet/ShelterApplicationController.php` | заявка приюта |
| `app/Http/Controllers/Admin/ShelterModerationController.php` | модерация приютов |
| `app/Http/Controllers/CabinetStubController.php` | временные стартовые страницы кабинетов |
| `app/Http/Requests/Auth/*`, `Cabinet/ShelterApplicationRequest.php`, `Admin/ModerationReasonRequest.php` | валидация |
| `app/Http/Middleware/EnsureUserHasRole.php`, `EnsureUserIsNotBlocked.php`, `bootstrap/app.php` | middleware, редиректы |
| `app/Policies/*` | 6 политик |
| `app/Services/ShelterRegistration.php`, `ShelterModeration.php` | бизнес-логика приютов |
| `app/Rules/BinIin.php` | БИН/ИИН |
| `app/Models/User.php` | `worksAt()`, `ownsShelter()`, `homeRoute()` |
| `app/Providers/AppServiceProvider.php` | политики, лимиты, `Password::defaults()` |
| `routes/web.php` | маршруты аутентификации, кабинетов, модерации |
| `resources/views/layouts/stub.blade.php`, `auth/*`, `cabinet/*` | технические формы |
| `lang/ru.json`, `lang/ru/{auth,passwords,validation}.php` | русские тексты |
| `tests/Concerns/CreatesActors.php`, `tests/Feature/{Auth,Shelters,Policies}/*`, `tests/Unit/Rules/BinIinTest.php` | тесты |
| `README.md`, `docs/PLAN.md` | вход в демо, статус этапа |

## Принятые решения
- **Без `Gate::before` для админа**: права админа прописаны в каждой политике явно — он управляет любым
  контентом и модерирует, но не бронирует выгулы, не подаёт заявки и не обрабатывает заявки на питомцев
  (только просмотр), как в матрице `roles.md`.
- **«📝 после модерации»**: черновики анкет и сборов можно создавать до одобрения (`create`, `update`),
  а выход из черновика — отдельная способность `publish` (только у `approved`). У слотов выгула черновиков
  нет, поэтому `WalkSlotPolicy::create` требует одобренный приют.
- **Политики решают «кто», а не бизнес-правила**: места, сроки брони/отмены, лимит 3 брони, дубли заявок —
  в сервисах этапов 8 и 10. `book` проверяет только роль, «не свой приют», одобренный приют и `open`-слот;
  `cancel` — свою активную запись.
- **Переходы статусов модерации — в политике** (`approve` только для `pending` и т.д.): одно место правды,
  и контроллер не может применить недопустимый переход.
- **Права на заявку приюта — в `ShelterApplicationRequest::authorize()`**, чтобы 403 возвращался раньше
  ошибок валидации.
- **Админ не имеет кабинета приюта** (`/shelter` только для `shelter_staff`); `/cabinet` открыт всем
  вошедшим (у админа там будут его пожертвования).
- **Телефон приюта**: долг этапа 5 закрыт без изменений `Phone` — все казахстанские номера, включая городские
  (`+7 727…`, `+7 7172…`), имеют вид `+7 7XX XXX XX XX` и проходят текущую проверку; для формы приюта —
  своё сообщение об ошибке с примером городского номера.
- **Ответ «ссылка отправлена» одинаков** для любой почты — защита от перебора адресов.
- **Тесты не зависят от GD**: логотип в тестах — настоящий PNG 1×1 (`createWithContent`), т.к. в
  `php:8.4-cli` нет GD.

## Отклонения от плана
- Добавлены `AdoptionApplicationPolicy` (была в `roles.md`, но не в списке плана) и правило `BinIin`.
- Сделаны технические Blade-формы и заглушки кабинетов — без них не проверить вход и права. Скилл
  `frontend-design` не вызывался: дизайна на этапе нет, формы будут свёрстаны по макетам на этапе 7
  (`layouts/stub.blade.php` будет заменён настоящим layout).
- Экран очереди модерации не сделан — только действия (этап 11). Уведомления о решении модерации — этап 12.
- Скриншоты не делались: страницы технические, без дизайна.

## Как проверялось
Локальный PHP 8.3 не подходит к lock-файлу (PHP 8.4: один тест падал на `ReflectionProperty::isVirtual()`),
поэтому тесты гонялись в контейнере `php:8.4-cli` (образ с `mirror.gcr.io` — Docker Hub отвечал 429).

| Проверка | Результат |
|----------|-----------|
| `vendor/bin/phpunit` в `php:8.4-cli` (SQLite in-memory) | ✅ 167 тестов, 1005 проверок |
| `./vendor/bin/pint --test` | ✅ |
| `npm run build` | ✅ |
| PostgreSQL, `docker compose up` | ⚠️ локально не проверялось — проверит CI (новых миграций нет, pgsql-специфики нет) |

```bash
docker compose exec app php artisan test
```

## Известные проблемы и долги
- `php artisan test` в `php:8.4-cli` с лимитом памяти 128 МБ падает на `FactoriesTest` (Faker `realText`);
  с `-d memory_limit=1G` всё проходит. В CI и Docker-образе лимит другой — на запуск не влияет.
- Блокировка пользователя админом должна отменять его активные брони (`roles.md`) — сделать на этапе 11
  вместе с экраном пользователей.
- Подтверждение почты (`email_verified_at`) не требуется и не проверяется.
- Страница 429 — стандартная (фирменные ошибки — этап 13).

## Что важно знать на следующем этапе
- Этап 7: заменить `layouts/stub.blade.php` настоящим layout и переверстать `auth/*`,
  `cabinet/shelter-application.blade.php`, `cabinet/stub.blade.php` по макетам (имена полей и маршрутов
  трогать нельзя — на них завязаны тесты: `identifier`, `password`, `remember`, `agree`, `logo` …).
  Flash — `session('status')`, ошибки — `$errors`. В шапке: `@auth` → `route(auth()->user()->homeRoute())`,
  выход — форма `POST route('logout')`.
- Этапы 8–11: в контроллерах — `Gate::authorize('<ability>', $model)` / `$this->authorize`; способности
  перечислены в политиках (`publish`, `hide`, `apply`, `favorite`, `donate`, `manageReports`, `viewDonations`,
  `book`, `cancel`, `manage`, `process`, `withdraw`, `manageStaff` …). Группы маршрутов: `auth`,
  `role:shelter_staff`, `role:admin`. Для форм — `throttle:forms`.
- В политиках используется `$user->shelter()` (кэшируется в связи `shelters`) — после изменения состава
  приюта вызывать `$user->unsetRelation('shelters')` (как в `ShelterRegistration`).
- Роль в приюте: владелец — `ownsShelter()`; сотрудник — `worksAt()`.
