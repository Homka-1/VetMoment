@echo off
rem VetMoment: остановка контейнеров. Данные (база, загруженные файлы) сохраняются.
chcp 65001 >nul
cd /d "%~dp0"

docker compose down
if errorlevel 1 (
    echo [VetMoment] Не удалось остановить контейнеры. Запущен ли Docker Desktop?
    pause
    exit /b 1
)

echo [VetMoment] Остановлено. Данные сохранены; полный сброс базы: docker compose down -v
