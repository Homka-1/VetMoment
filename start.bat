@echo off
rem VetMoment: запуск на Windows. Нужен только Docker Desktop.
chcp 65001 >nul
setlocal EnableDelayedExpansion
cd /d "%~dp0"
title VetMoment

if "%APP_PORT%"=="" set "APP_PORT=8000"

where docker >nul 2>&1
if errorlevel 1 goto no_docker

docker info >nul 2>&1
if not errorlevel 1 goto docker_ready

echo [VetMoment] Docker Desktop не запущен. Пробуем запустить его...
if exist "%ProgramFiles%\Docker\Docker\Docker Desktop.exe" (
    start "" "%ProgramFiles%\Docker\Docker\Docker Desktop.exe"
) else (
    goto docker_not_running
)
set /a tries=0
:wait_docker
timeout /t 3 /nobreak >nul
docker info >nul 2>&1
if not errorlevel 1 goto docker_ready
set /a tries+=1
if !tries! geq 60 goto docker_not_running
goto wait_docker

:docker_ready
echo [VetMoment] Собираем и запускаем контейнеры.
echo [VetMoment] Первый запуск занимает 5-10 минут: скачиваются образы и собирается приложение.
docker compose up -d --build
if errorlevel 1 goto compose_failed

echo [VetMoment] Ждём готовности приложения...
set /a tries=0
:wait_app
set "status="
for /f "delims=" %%s in ('docker inspect -f "{{.State.Health.Status}}" vetmoment_app 2^>nul') do set "status=%%s"
if "!status!"=="healthy" goto ready
set /a tries+=1
if !tries! geq 100 goto compose_failed
timeout /t 3 /nobreak >nul
goto wait_app

:ready
echo.
echo [VetMoment] Готово! Сайт: http://localhost:%APP_PORT%
echo [VetMoment] Остановить: stop.bat
start "" "http://localhost:%APP_PORT%"
exit /b 0

:no_docker
echo [VetMoment] Docker не найден.
echo Установите Docker Desktop: https://www.docker.com/products/docker-desktop/
echo После установки перезагрузите компьютер и снова запустите start.bat.
pause
exit /b 1

:docker_not_running
echo [VetMoment] Docker Desktop не запущен.
echo Откройте Docker Desktop, дождитесь надписи "Engine running" и снова запустите start.bat.
pause
exit /b 1

:compose_failed
echo [VetMoment] Не удалось запустить приложение.
echo Посмотреть журнал: docker compose logs app
echo Частые причины: занят порт %APP_PORT% или не хватает места на диске - см. README.md, раздел "Если что-то пошло не так".
pause
exit /b 1
