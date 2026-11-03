# Требования к данным

Уточняет список сущностей этапа 5 в `docs/PLAN.md`. Типы указаны логически; конкретные миграции —
на этапе 5. Общие правила:

- деньги — в **копейках**, `unsignedBigInteger`;
- даты/время — UTC в БД, отображение в `Europe/Moscow`;
- статусы — PHP backed enums (`string`), хранятся как `string` (без pgsql-специфичных типов);
- у публичных сущностей — `slug` (уникальный, латиница), ЧПУ;
- поиск без учёта регистра — переносимо (`LOWER(col) LIKE LOWER(?)`), без `ILIKE`;
- все сущности — `created_at`/`updated_at`; «удаление» публичного контента — через статус `archived`
  или soft deletes, чтобы не ломать историю пожертвований и выгулов.

## 1. Пользователь — `users`

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| login | string(32) | ✅ | уникальный, `[a-z0-9_.-]`, 3–32 |
| name | string(100) | ✅ | имя для отображения |
| phone | string(16) | ✅ | E.164 (`+7XXXXXXXXXX`), уникальный, нормализация `App\Support\Phone` |
| email | string(255) | ✅ | уникальный — для восстановления пароля и квитанций |
| password | hash | ✅ | ≥ 8 символов |
| role | enum `Role` | ✅ | `volunteer` по умолчанию |
| avatar_path | string | — | jpg/png/webp ≤ 2 МБ |
| is_blocked | bool | ✅ | по умолчанию false |
| birth_year | smallint | — | для проверки 18+ при записи на выгул (или чекбокс-подтверждение) |

Вход — по логину **или** телефону + пароль.

## 2. Приют — `shelters`, `shelter_user`

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| name | string(150) | ✅ | |
| slug | string | ✅ | уникальный |
| description | text | ✅ | 50–5000 символов |
| city | string(100) | ✅ | |
| address | string(255) | ✅ | |
| phone | string(16) | ✅ | E.164 |
| email | string | ✅ | |
| website / social_links | string(255) | — | URL |
| inn / ogrn | string | — | только цифры, 10/12 и 13/15 |
| logo_path | string | — | |
| walk_rules | text | — | правила выгула, показываются при бронировании |
| status | enum `ShelterStatus` | ✅ | `pending`, `approved`, `rejected`, `blocked` |
| moderation_comment | text | — | причина отказа/блокировки |
| approved_at | datetime | — | |

`shelter_user`: `shelter_id`, `user_id` (уникальный — один приют на пользователя), `role` (`owner`, `staff`).

## 3. Питомец — `pets`, `pet_photos`

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| shelter_id | FK | ✅ | |
| name | string(50) | ✅ | кличка |
| slug | string | ✅ | кличка + суффикс: `barsik-7f3a` |
| species | enum `Species` | ✅ | `cat`, `dog`, `other` |
| sex | enum `Sex` | ✅ | `male`, `female`, `unknown` |
| birth_date | date | — | либо точная, либо приблизительная |
| birth_date_is_approx | bool | ✅ | «≈ 2 года» |
| size | enum `PetSize` | ✅ | `small` (до 10 кг), `medium` (10–25), `large` (25+) |
| breed | string(100) | — | по умолчанию «метис» |
| color | string(100) | — | |
| character | text | ✅ | 20–2000 |
| traits | набор флагов | — | `good_with_kids`, `good_with_cats`, `good_with_dogs`, `house_trained`, `leash_trained` — отдельные bool-колонки (переносимо) |
| vaccinated / sterilized / chipped | bool | ✅ | |
| health_notes | text | — | особенности здоровья |
| story | text | — | история |
| status | enum `PetStatus` | ✅ | `draft`, `looking_for_home`, `on_treatment`, `reserved`, `adopted`, `archived` |
| can_walk | bool | ✅ | доступен для выгула (только собаки — подсказка, не ограничение) |
| guardianship_available | bool | ✅ | можно взять под опеку |
| arrived_at | date | — | в приюте с… («ждёт N месяцев», сортировка) |
| adopted_at | datetime | — | ставится при `adopted` |
| is_hidden | bool | ✅ | скрыт модератором |

Публично видны: статусы `looking_for_home`, `on_treatment`, `reserved`, `adopted` (последний — в блоке
«Нашли дом», не в основном каталоге), при `shelters.status = approved` и `is_hidden = false`.

Возраст в фильтре: «до 1 года», «1–3», «3–7», «7+» — вычисляется из `birth_date`.

`pet_photos`: `pet_id`, `path`, `sort` (int), `is_cover` (bool, ровно одна обложка на питомца).
Ограничения: 1–10 фото, jpg/png/webp, ≤ 8 МБ, ресайз до 1600 px по длинной стороне + превью 600 px.

## 4. Заявка на питомца — `adoption_applications`

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| pet_id, user_id | FK | ✅ | |
| type | enum `ApplicationType` | ✅ | `adoption`, `guardianship` |
| contact_name, contact_phone | string | ✅ | копия из профиля на момент подачи |
| city | string | ✅ | |
| experience | text | — | опыт содержания |
| living_conditions | text | для `adoption` | жильё, другие животные, дети |
| message | text | — | ≤ 2000 |
| status | enum `ApplicationStatus` | ✅ | `new`, `in_review`, `approved`, `rejected`, `withdrawn`, `completed` |
| shelter_comment | text | — | причина отказа / заметка |
| processed_at | datetime | — | |

Уникальность: одна активная (`new`, `in_review`, `approved`) заявка на (`pet_id`, `user_id`, `type`) —
проверяется в сервисе (частичных индексов не используем ради переносимости).

## 5. Сбор — `donation_campaigns`, `campaign_reports`

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| shelter_id | FK | ✅ | |
| pet_id | FK | — | сбор на конкретного питомца |
| title | string(120) | ✅ | |
| slug | string | ✅ | уникальный |
| short_description | string(200) | ✅ | для карточки |
| description | text | ✅ | на что нужны деньги, смета |
| goal_amount | unsignedBigInteger (коп.) | ✅ | 1 000 ₽ – 10 000 000 ₽ |
| raised_amount | unsignedBigInteger (коп.) | ✅ | **денормализация**, пересчёт только по `succeeded` пожертвованиям в транзакции |
| donations_count | unsignedInteger | ✅ | денормализация |
| starts_at | datetime | ✅ | |
| ends_at | datetime | — | ≤ 1 год от старта; пусто — бессрочный до достижения цели |
| allow_overfunding | bool | ✅ | принимать сверх цели до `ends_at` |
| cover_path | string | — | |
| status | enum `CampaignStatus` | ✅ | `draft`, `active`, `completed` (цель достигнута), `closed` (срок истёк / закрыт приютом), `archived` |
| is_hidden | bool | ✅ | модерация |
| completed_at | datetime | — | |

Прогресс = `raised_amount / goal_amount`, ограничен 100 % в прогресс-баре (текстом — реальная сумма).

`campaign_reports`: `campaign_id`, `title`, `body` (text), `spent_amount` (коп., опц.),
`published_at`, вложения — `report_attachments` (`path`, `type`: фото/чек, до 10 шт.).

## 6. Пожертвование — `donations`

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| uuid | uuid | ✅ | публичный идентификатор (`/donations/{uuid}`) |
| campaign_id | FK | — | null → в общий фонд приюта |
| shelter_id | FK | ✅ | всегда заполнен (для отчётов приюта) |
| user_id | FK | — | null для гостя |
| donor_name | string(100) | — | |
| donor_email | string | ✅ | для квитанции/чека |
| is_anonymous | bool | ✅ | публично показывается «Аноним» |
| amount | unsignedBigInteger (коп.) | ✅ | 50 ₽ – 1 000 000 ₽ |
| comment | string(500) | — | публикуется, если не анонимно |
| status | enum `PaymentStatus` | ✅ | `pending`, `succeeded`, `failed`, `expired`, `refunded` |
| provider | string | ✅ | `fake`, позже `yookassa` / `cloudpayments` |
| provider_payment_id | string | — | уникальный (провайдер + id) — идемпотентность webhook |
| idempotency_key | uuid | ✅ | уникальный, для создания платежа |
| paid_at | datetime | — | |

Пресеты сумм: 300, 500, 1 000, 3 000 ₽. Пересчёт `raised_amount` — только переходы в/из `succeeded`.

## 7. Слоты и записи на выгул — `walk_slots`, `walk_slot_pet`, `walk_bookings`

`walk_slots`:

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| shelter_id | FK | ✅ | |
| starts_at, ends_at | datetime | ✅ | длительность 30–180 мин; шаг 15 мин; создание не далее 8 недель вперёд |
| capacity | unsignedSmallInteger | ✅ | 1–20 человек |
| booked_count | unsignedSmallInteger | ✅ | денормализация (активные: `pending` + `confirmed`), обновляется под блокировкой |
| meeting_point | string(255) | — | место сбора |
| status | enum `WalkSlotStatus` | ✅ | `open`, `cancelled` (`full` и `past` — вычисляемые, не храним) |
| template_id | FK | — | если создан шаблоном |

`walk_slot_pet`: `walk_slot_id` + `pet_id` (уникальная пара) — питомцы, доступные в слоте;
пусто → «питомца назначит приют».

`walk_bookings`:

| Поле | Тип | Обяз. | Правила |
|------|-----|:-----:|---------|
| walk_slot_id, user_id | FK | ✅ | |
| pet_id | FK | — | выбранный питомец |
| status | enum `BookingStatus` | ✅ | `pending`, `confirmed`, `rejected`, `cancelled`, `completed`, `no_show` |
| comment | string(500) | — | |
| shelter_comment | string(500) | — | причина отказа |
| cancelled_by | enum | — | `user`, `shelter`, `system` |
| confirmed_at / cancelled_at | datetime | — | |

**Правила бронирования** (реализуются в `App\Services\WalkBookingService`, этап 10):

| № | Правило | Значение по умолчанию (config `walks.php`) |
|---|---------|--------------------------------------------|
| W1 | нельзя бронировать прошедший слот и слот, начинающийся раньше чем через | 2 ч |
| W2 | горизонт бронирования | 14 дней |
| W3 | нельзя бронировать отменённый или заполненный слот (`booked_count >= capacity`) | — |
| W4 | один питомец — не более одной активной брони в слоте | уникальность (`walk_slot_id`, `pet_id`) среди активных, проверка под блокировкой |
| W5 | пользователь — не более одной активной брони в слоте и без пересечений по времени | — |
| W6 | лимит активных будущих броней на пользователя | 3 |
| W7 | отмена пользователем не позднее чем за | 3 ч |
| W8 | питомец должен быть `can_walk = true` и в статусе `looking_for_home`/`on_treatment`/`reserved` | — |
| W9 | бронирование — в транзакции с `lockForUpdate()` на слот | защита от гонок |
| W10 | после 3 неявок (`no_show`) за 90 дней — запись только с подтверждением приюта и предупреждение | 3 |

`walk_templates` (шаблон на неделю): `shelter_id`, `weekdays` (битовая маска int, переносимо),
`start_time`, `duration_minutes`, `capacity`, `valid_from`, `valid_until`, питомцы — через `walk_template_pet`.

## 8. Прочее

- `favorites`: `user_id` + `pet_id` (уникальная пара).
- `notifications`: стандартная таблица Laravel (database-канал).
- Справочник городов не вводим — свободный ввод с подсказками из уже существующих значений.

## 9. Нефункциональные требования

| Область | Требование |
|---------|-----------|
| Устройства | mobile first; адаптив 360–1920 px; основные сценарии (каталог → выгул, пожертвование) проходимы с телефона одной рукой |
| Доступность | WCAG 2.1 AA: контраст ≥ 4.5:1 (текст), ≥ 3:1 (крупный текст, UI), видимый фокус, навигация с клавиатуры, `alt` у фото (по умолчанию «Кличка — вид, фото N»), `label` у полей, ошибки связаны с полями через `aria-describedby`, статусы не только цветом |
| Язык | интерфейс, письма, ошибки валидации — на русском; форматы: `1 500 ₽`, `3 ноября, 14:00`, склонения («2 года», «5 месяцев», «осталось 3 места») |
| Производительность | TTFB главной ≤ 500 мс в Docker на демо-данных; без N+1 (eager loading, проверка в тестах); изображения webp + `loading="lazy"`; JS ≤ 50 КБ gzip; страницы рендерятся сервером (Blade) и работают без JS для ключевых форм |
| Безопасность | CSRF, экранирование вывода, валидация через Form Requests, rate limit (вход, формы заявок, пожертвования), проверка mime/размера загрузок, политики доступа; контакты заявителей и записавшихся видит только их приют |
| Данные | согласие на обработку ПДн в формах; без реальных платёжных ключей в репозитории |
| Переносимость | PostgreSQL 15 и SQLite; запуск на Windows только с Docker Desktop (см. `docs/PLAN.md`) |
| SEO | ЧПУ, мета-теги, Open Graph для питомцев и сборов, `sitemap.xml` |
| Часовой пояс | `Europe/Moscow` для отображения; в MVP все приюты в одном часовом поясе (см. долги) |
