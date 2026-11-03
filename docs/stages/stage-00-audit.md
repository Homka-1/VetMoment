# Этап 0. Аудит текущего проекта

Дата: 2026-11-03 · Статус: ✅ готов

## Что есть сейчас

**Назначение:** «ВетМомент» — лента срочных запросов о помощи животным. Пользователь публикует
запрос (заголовок + текст), другой пользователь нажимает «Я помогу!», автору приходит
database-уведомление с телефоном помощника, автор закрывает заявку.

**Стек:**
- PHP 8.3+/8.4, Laravel 13, Blade (без Livewire/Inertia).
- Tailwind CSS 4 через `@tailwindcss/vite`, Vite 8, `resources/js/app.js` пустой.
- PostgreSQL 15 в Docker; `docker-compose.yml`: `app` (php:8.4-apache), `vite` (node:20), `db`.
- `start.sh` — chmod storage + `migrate --force` + apache.

**Код:**
| Файл | Содержимое |
|------|-----------|
| `routes/web.php` | главная, guest (login/register), auth (logout, profile, notifications, requests store/accept/close) |
| `app/Models/User.php` | поля `login`, `phone`, `password`; `hasMany AnimalRequest` |
| `app/Models/AnimalRequest.php` | `user_id`, `helper_id`, `title`, `body`, `status` (active/in_progress/resolved) |
| `app/Http/Controllers/AuthController.php` | регистрация/вход по логину, нормализация телефона в E.164 |
| `app/Http/Controllers/ProfileController.php` | редактирование профиля (дубль `normalizePhone`) |
| `app/Http/Controllers/AnimalRequestController.php` | лента, создание, принятие, закрытие |
| `app/Notifications/RequestAccepted.php` | database-уведомление |
| `resources/views/*` | layout, home, auth, profile, notifications, неиспользуемый `welcome` |

## Проблемы и долги

1. Доменная модель не соответствует теме 79 — нет приютов, питомцев, пожертвований, выгулов.
2. `normalizePhone()` продублирован в двух контроллерах.
3. `UserFactory` и `DatabaseSeeder` используют `name`/`email`, которых нет в таблице `users` —
   `db:seed` упадёт.
4. Валидация в контроллерах, нет Form Requests и Policies; `accept` не проверяет, что пользователь
   не автор (проверка только во view).
5. Нет ролей пользователей.
6. Счётчик уведомлений в layout грузит коллекцию (`unreadNotifications->count()`), а не делает `count()` в БД.
7. Шрифт Inter подключается через `@import` внутри `<style>`, при этом в `@theme` объявлен Instrument Sans —
   нет единой дизайн-системы, стили набраны утилитами «по месту», без компонентов.
8. `.env.example`: `DB_CONNECTION=sqlite`, `APP_LOCALE=en`, хотя проект на pgsql и на русском.
9. Нет тестов кроме примеров, нет CI, нет линтера в пайплайне.
10. Нет загрузки файлов (`storage:link`), нет очереди-воркера в Docker.

## Что переиспользуем

- Docker-окружение (app + vite + db) и настройки Vite (HMR в Docker).
- Laravel 13 + Tailwind 4 как стек.
- Логику нормализации телефона (вынести в сервис).
- Database-уведомления как механизм.

## Решения для следующих этапов

- Старый домен `AnimalRequest` удаляется на этапе 4 (не мигрируем данные — разработка «с нуля»).
- Дизайн создаётся заново на этапах 2–3 (SVG-макеты, совместимые с Adobe Illustrator).
- Полный план — `docs/PLAN.md`.
