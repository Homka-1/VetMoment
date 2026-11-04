# VetMoment

Сервис помощи бездомным животным и приютам Казахстана: **анкеты питомцев, сбор пожертвований, запись на выгул**.

Стек: Laravel 13 (PHP 8.4), Blade, Tailwind CSS 4 + Vite, PostgreSQL 15, Docker Compose.
План разработки и статусы этапов — [`docs/PLAN.md`](docs/PLAN.md).

> Проект в разработке: сейчас готова техническая основа (этап 4). Страницы сайта появятся с этапа 7.

---

## Запуск на Windows за 3 шага

Нужен только **Docker Desktop**. PHP, Composer, Node.js и PostgreSQL ставить не нужно — всё собирается внутри Docker.

1. **Установите Docker Desktop** — <https://www.docker.com/products/docker-desktop/>.
   При установке оставьте галочку «Use WSL 2», после установки перезагрузите компьютер и один раз
   откройте Docker Desktop (дождитесь надписи «Engine running»).
2. **Скачайте проект**: на GitHub кнопка **Code → Download ZIP**, распакуйте архив
   (или `git clone`, если установлен Git).
3. **Дважды кликните `start.bat`** в папке проекта.

Первый запуск занимает 5–10 минут (скачиваются образы, собирается приложение), следующие — секунды.
Когда всё готово, браузер сам откроет **<http://localhost:8000>**. База данных уже создана и заполнена
демо-данными.

Остановить — `stop.bat` (данные сохраняются).

### Демо-доступ

Пароль у всех демо-пользователей — `password`. Страница входа появится на этапе 6.

| Логин | Роль | Что посмотреть |
|-------|------|----------------|
| `volunteer` | волонтёр (Айгерим) | записи на выгул, заявки на питомцев, избранное, пожертвования |
| `shelter` | представитель приюта «Дом лап», Алматы (владелец) | питомцы, сборы, слоты и записи приюта |
| `murlyka`, `teplyugol`, `hvostiki` | владельцы приютов в Астане, Шымкенте, Караганде | то же для своих приютов |
| `kotopes` | приют «Котопёс», Актобе — заявка на модерации | кабинет в режиме черновика |
| `admin` | администратор | модерация приютов, пользователи, статистика |

Демо-данные: 4 проверенных приюта и 1 на модерации, 32 питомца, 9 сборов (с пожертвованиями и отчётами),
расписание выгулов на неделю вперёд и две недели истории. Даты считаются от момента первого запуска.

### То же самое из командной строки

Если удобнее PowerShell или cmd (а также для macOS и Linux) — в папке проекта:

```bash
docker compose up -d --build
```

Остановить:

```bash
docker compose down
```

Полный сброс (удалить базу и загруженные файлы, при следующем запуске демо-данные создадутся заново):

```bash
docker compose down -v
```

### Если что-то пошло не так

- **«Docker Desktop не запущен»** — откройте Docker Desktop и дождитесь «Engine running», затем снова `start.bat`.
- **Порт 8000 занят** другой программой — запустите на другом порту. В PowerShell:

  ```bash
  $env:APP_PORT=8080; docker compose up -d --build
  ```

  и откройте <http://localhost:8080>.
- **Посмотреть, что происходит внутри**:

  ```bash
  docker compose logs app
  ```

- **После обновления кода сайт не изменился** — пересоберите образ: `start.bat` (он всегда запускает
  `docker compose up -d --build`).

---

## Запасной способ: без Docker (Laravel Herd + SQLite)

Если на компьютере не работает виртуализация и Docker Desktop не запускается.

1. Установите **Laravel Herd for Windows** — <https://herd.laravel.com/windows> (в нём уже есть PHP 8.4,
   Composer и Node.js). PostgreSQL не нужен: база — один файл SQLite.
2. В папке проекта выполните в терминале Herd (PowerShell или cmd) по очереди:

   ```bash
   copy .env.sqlite.example .env
   ```

   ```bash
   composer install
   ```

   ```bash
   php artisan key:generate
   ```

   ```bash
   type nul > database\database.sqlite
   ```

   ```bash
   php artisan migrate --seed
   ```

   ```bash
   php artisan storage:link
   ```

   ```bash
   npm install
   ```

   ```bash
   npm run build
   ```

   ```bash
   php artisan serve
   ```

3. Откройте <http://localhost:8000>.

В этом режиме очередь выполняется сразу (`QUEUE_CONNECTION=sync`), отдельный воркер не нужен.

---

## Режим разработки

Код монтируется в контейнер с диска, CSS/JS пересобираются на лету (Vite HMR), письма перехватывает Mailpit:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

| Что | Адрес |
|-----|-------|
| Сайт | <http://localhost:8000> |
| Vite dev server | <http://localhost:5173> |
| Mailpit (письма) | <http://localhost:8025> |
| PostgreSQL | `localhost:5432`, база `vetmoment`, пользователь `vet_user` / `vet_pass` |

При первом запуске контейнер сам выполнит `composer install` (с dev-пакетами), а сервис `vite` — `npm ci`.

Полезные команды:

```bash
docker compose exec app php artisan test
```

```bash
docker compose exec app ./vendor/bin/pint
```

```bash
docker compose exec app php artisan migrate:fresh --seed
```

```bash
docker compose logs -f app queue
```

### Как устроен запуск

| Файл | Назначение |
|------|-----------|
| `Dockerfile` | многоэтапная сборка: Composer-зависимости → сборка CSS/JS (Node) → PHP 8.4 + Apache |
| `docker-compose.yml` | режим запуска: `app` (сайт, :8000), `queue` (воркер очереди), `db` (PostgreSQL 15, наружу не открыт) |
| `docker-compose.dev.yml` | режим разработки: bind-mount кода, `vite`, `mailpit`, порт БД наружу |
| `docker/start.sh` | точка входа: `.env` из примера, `APP_KEY` (хранится в томе `storage`), ожидание БД, миграции, демо-данные при пустой базе, `storage:link`, кеш конфигурации |
| `start.bat` / `stop.bat` | запуск и остановка на Windows двойным кликом |
| `.env.example` | настройки для Docker (PostgreSQL) |
| `.env.sqlite.example` | настройки для запуска без Docker (SQLite) |

Переменные, которые можно задать перед запуском: `APP_PORT` (порт сайта, по умолчанию `8000`),
`APP_DEBUG` (`true` — подробные ошибки), `DB_PORT` (порт PostgreSQL в режиме разработки).

---

## Соглашения

- Интерфейс только на русском. Валюта — тенге (₸), суммы хранятся в тиынах.
- Телефоны — казахстанские мобильные `+7 7XX XXX XX XX`, хранятся как `+77XXXXXXXXX`
  (нормализация — `App\Support\Phone`, правило валидации — `App\Rules\KazakhPhone`).
- Время в БД — UTC, показывается в `Asia/Almaty` (`config('app.display_timezone')`).
- Код не зависит от СУБД: тесты идут на SQLite, CI дополнительно гоняет их на PostgreSQL 15.

Инструкции для разработки по этапам — [`CLAUDE.md`](CLAUDE.md), заметки по этапам — [`docs/stages/`](docs/stages/).
