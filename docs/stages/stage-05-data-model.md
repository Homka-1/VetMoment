# Этап 5. Модель данных: миграции, модели, фабрики, сиды

Дата: 2026-11-04 · Статус: ✅ готов

## Цель этапа
Схема БД под три ключевые функции (анкеты питомцев, пожертвования, выгул): миграции с индексами и внешними
ключами, модели с enum-кастами и связями, фабрики, сидер с демо-данными как на макетах, ER-диаграмма.

## Что сделано
- **Миграции** (`database/migrations/2026_11_04_00000{1..6}_*`) — 15 таблиц по `docs/product/data-requirements.md`:
  `shelters`, `shelter_user`, `pets`, `pet_photos`, `favorites`, `adoption_applications`, `donation_campaigns`,
  `campaign_reports`, `report_attachments`, `donations`, `walk_templates`, `walk_template_pet`, `walk_slots`,
  `walk_slot_pet`, `walk_bookings`. Таблица `users` дополнена правкой исходной миграции: `name`, `email`,
  `email_verified_at`, `role`, `avatar_path`, `is_blocked`, `birth_year` (длины полей — по требованиям).
- **Enum-ы** (`app/Enums`): `Role`, `ShelterMemberRole`, `ShelterStatus`, `Species`, `Sex`, `PetSize`, `PetStatus`,
  `ApplicationType`, `ApplicationStatus`, `CampaignStatus`, `PaymentStatus`, `WalkSlotStatus`, `BookingStatus`,
  `CancelledBy`, `AttachmentType`, вычисляемый `WalkSlotState` и `Tone` (тон бейджа). У каждого — `label()`
  (подписи из `docs/design/tokens.md`), у статусов — `tone()`; трейт `HasLabel` даёт `values()` / `options()`.
  Группы статусов: `PetStatus::public()/catalog()/walkable()`, `BookingStatus::active()`,
  `ApplicationStatus::active()`, `CampaignStatus::public()`; признаки рисовки `isDashed()` / `isSolid()`.
- **Модели** (`app/Models`): `Shelter`, `ShelterMember` (pivot `shelter_user` с кастом роли), `Pet`, `PetPhoto`,
  `AdoptionApplication`, `DonationCampaign`, `CampaignReport`, `ReportAttachment`, `Donation`, `WalkTemplate`,
  `WalkSlot`, `WalkBooking`; `User` расширен (роль, связи, `shelter()`, `isAdmin()`, `hasRole()`).
  - Трейт `Concerns\HasSlug`: латинский slug при создании (`dom-lap-almaty`, дубль → `-2`; у питомцев — суффикс
    `bublik-7f3a`), маршруты по slug. У `Donation` — `uuid` и `idempotency_key` генерируются сами, маршрут по `uuid`.
  - Скоупы: `Shelter::approved()`, `Pet::visible()` / `inCatalog()`, `DonationCampaign::visible()`,
    `WalkSlot::upcoming()`, `WalkBooking::active()`, `AdoptionApplication::active()`.
  - Логика данных: `WalkSlot::state()` (свободен / мало мест / мест нет / прошёл / отменён), `spotsLeft()`,
    `recalculateBookedCount()`; `DonationCampaign::progressPercent()` (0–100), `recalculateTotals()`
    (только `succeeded`); `Pet::ageInMonths()`, `traitLabels()`, `isWalkable()` (W8);
    `WalkTemplate::maskFromDays()`, `hasWeekday()`, `weekdayList()` (битовая маска дней).
- **Фабрики** для всех моделей со состояниями (`admin()`, `shelterStaff()`, `pending()`, `withOwner()`,
  `status(...)`, `forCampaign()`, `cancelled()`, `past()` …); телефоны — валидные казахстанские.
- **Демо-данные** (`DemoSeeder` + `database/seeders/data/{shelters,pets,campaigns}.php`), всё как на макетах:
  - 4 одобренных приюта — «Дом лап» (Алматы), «Мурлыка» (Астана), «Тёплый угол» (Шымкент), «Хвостики» (Караганда)
    — и «Котопёс» (Актобе) на модерации; владельцы-сотрудники, у «Дома лап» ещё один сотрудник;
  - 32 питомца (Бублик, Мята, Граф, Ромашка, Лорд, Барон, …) во всех статусах, с чертами, здоровьем, историями;
  - 9 сборов во всех статусах (суммы с макетов: «Операция на лапе для Ромашки» 412 500 из 600 000 ₸, 84 человека
    и т.д.), 533 пожертвования (в т.ч. в общий фонд, анонимные, ожидающие и неуспешные), 2 отчёта с вложениями;
  - 5 шаблонов выгула, ~44 слота (две недели истории + неделя вперёд), записи во всех статусах, один слот
    заполнен, один отменён приютом; соблюдены правила W4–W6 (тесты это проверяют);
  - заявки на усыновление/опеку во всех статусах, избранное.
- **Демо-«фото»**: 16 JPEG-портретов (собаки/кошки × окрас × фон) — рендер иллюстраций из UI-кита скриптом
  `design/tools/demo-photos.mjs` в `database/seeders/photos/` (356 КБ). Сидер копирует их на диск `public`
  (`storage/app/public/demo/pets/`).
- **ER-диаграмма** (Mermaid) + политика удаления, индексы, вычисляемые значения — `docs/architecture/er.md`.
- **README**: таблица демо-логинов (пароль у всех `password`).
- **Тесты**: +45 (всего 74): enum-ы, маска дней, slug, касты, скоупы видимости, пересчёт сумм и мест, состояния
  слота, уникальные ограничения (избранное, один приют на пользователя, идемпотентность платежа), все фабрики,
  демо-сидер (логины и роли, обложки и файлы фото, суммы сборов = сумме успешных пожертвований, правила выгула).

## Изменённые / созданные файлы
| Файл | Что |
|------|-----|
| `database/migrations/0001_01_01_000000_create_users_table.php` | новые поля `users` |
| `database/migrations/2026_11_04_*` | 6 миграций, 15 таблиц |
| `app/Enums/*` | 17 enum-ов + трейт `HasLabel` |
| `app/Models/*`, `app/Models/Concerns/HasSlug.php` | модели и slug |
| `database/factories/*` | 11 фабрик |
| `database/seeders/DatabaseSeeder.php`, `DemoSeeder.php`, `data/*.php`, `photos/*.jpg` | демо-данные |
| `design/tools/demo-photos.mjs` | генератор демо-фото из иллюстраций UI-кита |
| `docs/architecture/er.md` | ER-диаграмма |
| `tests/Unit/Enums`, `tests/Unit/Models`, `tests/Feature/Models`, `tests/Feature/DemoSeederTest.php` | тесты |
| `tests/Feature/DatabaseSetupTest.php` | демо-логин `admin` вместо `demo`, фейковый диск |
| `README.md`, `docs/PLAN.md` | демо-логины, статус и уточнение этапа |

## Принятые решения
- **Статусы — `string(20)` + enum-каст**, никаких `ENUM`/`CHECK`/`JSONB`: одна схема для PostgreSQL и SQLite.
- **Удаление**: история пожертвований защищена `restrict` (приют/сбор с пожертвованиями не удалить — только
  архивировать статусом); пользователь удалён → пожертвование становится гостевым (`null`); остальное — каскад.
  Soft deletes не вводились — «удаление» в интерфейсе = статус `archived`.
- **Идемпотентность webhook** — уникальный индекс `(provider, provider_payment_id)`; `NULL` не конфликтует
  ни в PostgreSQL, ни в SQLite, поэтому у ожидающих платежей `provider_payment_id` пустой.
- **Денормализованные счётчики** пересчитываются агрегатом (`recalculateTotals()`, `recalculateBookedCount()`),
  а не инкрементом — проще и без дрейфа. Сервисы этапов 9–10 вызывают их в транзакции / под `lockForUpdate()`.
- **«Мало мест»** — свободно ≤ 2 и в слоте уже кто-то записан (`WalkSlot::FEW_SPOTS_LEFT`); пустой слот на 2 места
  показывается «Свободен». Это правило отображения, поэтому константа, а не `config/walks.php`.
- **`shelter_user` с собственным `id`** и pivot-моделью `ShelterMember` — чтобы роль в приюте кастовалась в enum.
- **Slug у питомцев — с случайным суффиксом** (клички повторяются), у приютов/сборов — с числовым при дубле.
- **Демо-фото — иллюстрации из UI-кита**, а не фотографии: нет вопросов с лицензиями, файлы маленькие, стиль
  совпадает с макетами. Реальные фото приюты загрузят сами (этап 8).
- **Время в сидере считается от «сейчас»**: слоты всегда на ближайшую неделю, сборы «идут» в любой день запуска.
- **БИН в демо-данных** — с правильным контрольным разрядом (на случай валидации на этапе 6/11).
- `WithoutModelEvents` убран из `DatabaseSeeder`: slug и uuid генерируются в событии `creating`.

## Отклонения от плана
- В плане — «3 приюта, ~30 питомцев»; сделано 4 одобренных + 1 на модерации и 32 питомца — по заметке этапа 3
  (данные как на макетах, в т.ч. экран модерации). План поправлен.
- Демо-логин `demo` заменён набором ролевых (`admin`, `volunteer`, `shelter`, владельцы других приютов).
- Добавлены вспомогательные вещи сверх списка: enum-ы `WalkSlotState`, `Tone`, `ShelterMemberRole`,
  `CancelledBy`, `AttachmentType`; методы пересчёта счётчиков в моделях; генератор демо-фото.
- Правила бронирования W1–W10 и `config/walks.php` **не** реализованы — это этап 10 (здесь только данные,
  которые им удовлетворяют).

## Как проверялось
Docker в окружении агента недоступен; локально PHP 8.3 (зависимости поставлены с `--ignore-platform-req=php`).

| Проверка | Результат |
|----------|-----------|
| `php artisan test` (SQLite in-memory) | ✅ 74 теста, 621 проверка |
| `php artisan test` на PostgreSQL 16 (локальный кластер) | ✅ 74 теста |
| `php artisan migrate:fresh --seed` на SQLite и PostgreSQL | ✅ ~2 с; счётчики, места, W4–W6 сверены скриптом |
| `./vendor/bin/pint --test` | ✅ |
| `npm run build` | ✅ |
| Запуск через `docker compose up` | ⚠️ не проверялся локально — проверит CI (job с Docker) |

```bash
php artisan migrate:fresh --seed
```
```bash
php artisan test
```

## Известные проблемы и долги
- Сидер частично недетерминирован (`Str::random` в slug, `Collection::random`) — состав данных одинаков,
  но конкретные записи на выгул и суффиксы slug различаются между запусками.
- Вложения отчётов — только фото (иллюстрации), «чеков» в демо нет.
- Телефон приюта по требованиям может быть городским (`+7 727…`), а `App\Support\Phone` принимает только мобильные —
  решить при валидации формы приюта (этап 6/11): расширить `Phone` или отдельное правило.

## Что важно знать на следующем этапе
- Этап 6: роль — `User::$role` (`App\Enums\Role`), `isAdmin()`, `hasRole(...)`; приют пользователя —
  `$user->shelter()` (+ `pivot->role` = `ShelterMemberRole`); для «своё» в политиках сравнивать
  `$model->shelter_id` с `$user->shelter()?->id` (не забыть eager load `shelters`). `is_blocked` — запрет входа.
  Вход по логину или телефону: `login` и `phone` уникальны, телефон нормализуется `Phone::normalize()`.
  Демо-логины — в README, пароль `password` (`DemoSeeder::PASSWORD`).
- Публичная видимость — скоупы `Pet::visible()` / `inCatalog()`, `DonationCampaign::visible()`, `Shelter::approved()`.
- Этап 7: подписи и тоны бейджей — `->label()` / `->tone()` (`App\Enums\Tone`), пунктир — `isDashed()`,
  сплошной — `isSolid()`; списки для `<select>` — `Enum::options()`. Склонения возраста — от `Pet::ageInMonths()`.
  Фото — `PetPhoto::url()` (диск `public`).
- Этап 9: менять `raised_amount` только через `DonationCampaign::recalculateTotals()` в транзакции.
  Этап 10: бронирование — под `lockForUpdate()` на слот, затем `WalkSlot::recalculateBookedCount()`;
  слоты из шаблонов — по `WalkTemplate::hasWeekday()` (время шаблона — местное, хранить UTC).
- Демо-фото пересобираются `node design/tools/demo-photos.mjs`.
