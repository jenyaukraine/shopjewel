# 6MOMENTS for OpenCart

Продакшен работает на чистом OpenCart `4.1.0.3`. В продакшен-сборку код 6MOMENTS попадает только из расширения `opencart/sixmoments` и при каждом релизе накладывается поверх неизменённого ядра OpenCart.

## Архитектура

- `sixmoments-store` — PHP/Apache с официальным исходным кодом OpenCart и текущей версией модуля.
- `sixmoments-db` — MySQL 8.4.
- Docker volumes хранят БД, загруженные изображения и файлы цифровых товаров независимо от релиза контейнера. Код ядра и vendor-зависимости никогда не берутся из volume.
- Caddy остаётся общим edge-прокси и отправляет трафик в `sixmoments-store:3000`.

При первой загрузке с пустой БД контейнер запускает штатный CLI-инсталлятор OpenCart. На последующих запусках он только пересоздаёт конфигурацию из переменных окружения и сохраняет данные магазина.

## Локальный запуск

```powershell
Copy-Item .env.example .env
# Замените все пароли и OPENCART_ADMIN_EMAIL в .env.
docker network inspect web-edge 2>$null; if ($LASTEXITCODE) { docker network create web-edge }
docker compose --env-file .env -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Магазин будет доступен на `http://localhost:8080/`.

Контейнер автоматически регистрирует и включает `6MOMENTS Storefront Suite` при первой установке. При последующих запусках он восстанавливает обязательные события витрины и обновляет версионируемые изображения, сохраняя настройки и данные магазина.

## Пакет модуля

Установочный пакет модуля при необходимости можно собрать локально командой:

```powershell
Compress-Archive -Path opencart/sixmoments/* -DestinationPath sixmoments.ocmod.zip -Force
```

## Продакшен-деплой

Каждый push в `main` сразу запускает workflow `.github/workflows/deploy-production.yml` без отдельного CI-job с валидацией. Перед заменой только контейнеров проекта `sixmoments` workflow создаёт дамп MySQL, собирает образ из чистого OpenCart и текущего модуля, затем проверяет публичный URL. Общий Caddy и контейнеры соседних сайтов workflow не изменяет.

Repository variables:

- `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`
- `PRODUCTION_URL`
- `OPENCART_ADMIN_EMAIL`
- опционально `OPENCART_ADMIN_USERNAME`

Production secrets:

- `DEPLOY_SSH_KEY`, `DEPLOY_KNOWN_HOSTS`
- `OPENCART_DB_PASSWORD`, `OPENCART_DB_ROOT_PASSWORD`
- `OPENCART_ADMIN_PASSWORD` (от 5 до 20 символов — ограничение CLI-инсталлятора OpenCart 4.1)

Бэкапы БД хранятся в `$DEPLOY_PATH/backups` 14 дней. Значения admin-переменных используются только при первой установке; дальнейшие учётные данные живут в БД OpenCart.
