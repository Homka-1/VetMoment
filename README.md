# VetMoment
 
Laravel-приложение для публикации запросов о помощи животным и отклика волонтёров.
 
## Запуск через Docker
 
### Требования
 
- Docker
- Docker Compose (плагин `docker compose`)
 
### Быстрый старт
 
1. Скопируйте переменные окружения:
 
```bash
cp .env.example .env
```
 
2. Поднимите контейнеры и соберите образы:
 
```bash
docker compose up -d --build
```
 
Приложение будет доступно:
 
- **Web**: `http://localhost:8000`
- **PostgreSQL**: `localhost:5432`
- **Vite dev server (разработка)**: `http://localhost:5173`
 
### Про `.env` и `APP_KEY`
 
При первом запуске нужно, чтобы в `.env` был задан `APP_KEY`.
 
Если ключ пустой, сгенерируйте его:
 
```bash
docker compose exec app php artisan key:generate
```
 
## Tailwind / Vite в Docker
 
В проекте Tailwind подключён через Vite (`@vite(...)`), без CDN.
 
Есть два режима:
 
- **Dev (рекомендуется)**
  - поднимается сервис `vite`
  - стили и скрипты грузятся с `http://localhost:5173`
  - изменения применяются без пересборки PHP-образа
 
- **Prod-like (без Vite dev server)**
  - ассеты собираются в образе на этапе `docker build` (`npm run build`)
  - Laravel читает `public/build/manifest.json`
 
## Полезные команды
 
- Запустить/остановить:
 
```bash
docker compose up -d
docker compose down
```
 
- Посмотреть логи:
 
```bash
docker compose logs -f app
docker compose logs -f vite
docker compose logs -f db
```
 
- Запустить миграции вручную:
 
```bash
docker compose exec app php artisan migrate
```
 
## Troubleshooting
 
### `Illuminate\Foundation\ViteManifestNotFoundException` (нет `public/build/manifest.json`)
 
Причины:
 
- Vite dev server не запущен и нет `public/hot`
- `public/build` был перетёрт bind-mount'ом
 
Решение:
 
- Для разработки: убедитесь, что поднят сервис `vite`.
- Для режима без dev-сервера: пересоберите образ:
 
```bash
docker compose down
docker compose up -d --build
```
 
### Ошибка загрузки `http://0.0.0.0:5173/@vite/client`
 
Это происходит, когда Vite отдаёт клиенту `0.0.0.0` как адрес. В проекте это исправлено настройками `server.origin`/`server.hmr` в `vite.config.js`.
Если проблема повторилась — перезапустите Vite:
 
```bash
docker compose restart vite
```
