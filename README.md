# NOVERAILE for OpenCart

Продакшен работает на чистом OpenCart `4.1.0.3`, а релизный пакет еженедельно проверяется также на `4.0.2.3`. В продакшен-сборку код NOVERAILE попадает только из расширения `opencart/noveraile` и при каждом релизе накладывается поверх неизменённого ядра OpenCart.

## Архитектура

- `noveraile-store` — PHP/Apache с официальным исходным кодом OpenCart и текущей версией модуля.
- `noveraile-db` — MySQL 8.4.
- Docker volumes хранят БД, загруженные изображения и файлы цифровых товаров независимо от релиза контейнера. Код ядра и vendor-зависимости никогда не берутся из volume.
- Caddy остаётся общим edge-прокси и отправляет трафик в `noveraile-store:3000`.

При первой загрузке с пустой БД контейнер запускает штатный CLI-инсталлятор OpenCart. На последующих запусках он только пересоздаёт конфигурацию из переменных окружения и сохраняет данные магазина.

## Локальный запуск

```powershell
Copy-Item .env.example .env
# Замените все пароли и OPENCART_ADMIN_EMAIL в .env.
docker network inspect web-edge 2>$null; if ($LASTEXITCODE) { docker network create web-edge }
docker compose --env-file .env -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Магазин будет доступен на `http://localhost:8080/`.

Контейнер автоматически регистрирует и включает `NOVERAILE Storefront Suite` при первой установке. При последующих запусках он восстанавливает обязательные события витрины и обновляет версионируемые изображения, сохраняя настройки и данные магазина.

## Каталог поставщика

Ассортимент магазина живёт в фиде `opencart/noveraile/data/catalog-feed.json`. Поставщик экспортирует по одной строке на каждую комбинацию артикула, пробы золота и качества бриллианта; фид группирует их в товары.

Пересобрать фид из нового экспорта:

```powershell
node tools/build-catalog-feed.mjs "C:\путь\products.csv"
```

Скрипт проверяет экспорт (валюта, перечисления, ссылки на изображения, стабильность данных внутри артикула) и падает вместо того, чтобы записать неполный фид. Текущий фид: 476 товаров, 6855 вариантов цены, 3653 изображения.

Импорт запускается контейнером при старте и повторяем: товары переписываются только когда контрольная сумма фида изменилась, изображения докачиваются в фоне и продолжаются с того места, где остановился предыдущий контейнер.

```bash
docker exec noveraile-store noveraile-import-catalog --status
```

- `--if-needed` — пропустить каталог, если магазин уже соответствует фиду
- `--no-images` / `--images-only` — разделить запись каталога и загрузку фотографий
- `--budget=СЕКУНДЫ` — ограничить время загрузки изображений
- `--force` — переписать все товары

Переменные окружения контейнера: `NOVERAILE_IMPORT_CATALOG=0` отключает импорт, `NOVERAILE_IMPORT_TIMEOUT` и `NOVERAILE_IMPORT_IMAGE_BUDGET` задают лимиты.

Цена товара в OpenCart равна самой дешёвой комбинации, а каждое значение опции «Золото и качество бриллианта» добавляет точную разницу до цены поставщика. Фид не является частью продаваемого расширения: `npm run build:opencart` исключает `data/` из marketplace-архива.

## Пакет модуля

Marketplace-релиз собирается одной командой. Скрипт проверяет манифест, обязательные файлы, отсутствие dev-артефактов и live-секретов, затем создаёт установочный архив и комплект для покупателя:

```powershell
npm run test:opencart
npm run build:opencart
```

Результат: `release/noveraile.ocmod.zip`, полный delivery bundle с документацией и `SHA256SUMS.txt`. Тексты карточки товара и чек-лист продавца находятся в `marketplace/`.

## Продакшен-деплой

Каждый push в `main` сразу запускает workflow `.github/workflows/deploy-production.yml` без отдельного CI-job с валидацией. Перед заменой только контейнеров проекта `noveraile` workflow создаёт дамп MySQL, собирает образ из чистого OpenCart и текущего модуля, затем проверяет публичный URL. Общий Caddy и контейнеры соседних сайтов workflow не изменяет.

Repository variables:

- `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`
- `PRODUCTION_URL`
- `OPENCART_ADMIN_EMAIL`
- опционально `OPENCART_ADMIN_USERNAME`

Production secrets:

- `DEPLOY_SSH_KEY`, `DEPLOY_KNOWN_HOSTS`
- `OPENCART_DB_PASSWORD`, `OPENCART_DB_ROOT_PASSWORD`
- `OPENCART_ADMIN_PASSWORD` (от 5 до 20 символов — ограничение CLI-инсталлятора OpenCart 4.1)
- `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` — оба обязательны для автоматического включения Stripe Checkout; без них модуль остаётся выключенным

Бэкапы БД хранятся в `$DEPLOY_PATH/backups` 14 дней. Значения admin-переменных используются только при первой установке; дальнейшие учётные данные живут в БД OpenCart.
