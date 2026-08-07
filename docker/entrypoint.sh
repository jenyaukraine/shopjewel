#!/bin/sh
set -eu

required_variables="
OPENCART_HTTP_SERVER
OPENCART_ADMIN_USERNAME
OPENCART_ADMIN_PASSWORD
OPENCART_ADMIN_EMAIL
OPENCART_DB_HOST
OPENCART_DB_NAME
OPENCART_DB_USER
OPENCART_DB_PASSWORD
OPENCART_DB_PREFIX
"

for variable in $required_variables; do
    eval "value=\${$variable:-}"

    if [ -z "$value" ]; then
        echo "Missing required environment variable: $variable" >&2
        exit 1
    fi
done

cd /var/www/html

attempt=1
until php -r '
    $db = @new mysqli(
        getenv("OPENCART_DB_HOST"),
        getenv("OPENCART_DB_USER"),
        getenv("OPENCART_DB_PASSWORD"),
        getenv("OPENCART_DB_NAME"),
        (int)(getenv("OPENCART_DB_PORT") ?: 3306)
    );
    exit($db->connect_errno ? 1 : 0);
'; do
    if [ "$attempt" -ge 60 ]; then
        echo "Database did not become available" >&2
        exit 1
    fi

    echo "Waiting for the OpenCart database ($attempt/60)"
    attempt=$((attempt + 1))
    sleep 2
done

noveraile_seed_demo=1

if php -r '
    $db = new mysqli(
        getenv("OPENCART_DB_HOST"),
        getenv("OPENCART_DB_USER"),
        getenv("OPENCART_DB_PASSWORD"),
        getenv("OPENCART_DB_NAME"),
        (int)(getenv("OPENCART_DB_PORT") ?: 3306)
    );
    $table = $db->real_escape_string(getenv("OPENCART_DB_PREFIX") . "setting");
    $result = $db->query("SHOW TABLES LIKE \"{$table}\"");
    exit($result && $result->num_rows > 0 ? 0 : 1);
'; then
    echo "Existing OpenCart database found; preserving store data"
    noveraile_seed_demo=0
else
    echo "Empty database found; installing clean OpenCart"
    : > config.php
    : > admin/config.php

    install_output="$(php install/cli_install.php install \
        --username "$OPENCART_ADMIN_USERNAME" \
        --password "$OPENCART_ADMIN_PASSWORD" \
        --email "$OPENCART_ADMIN_EMAIL" \
        --http_server "$(printf '%s/' "${OPENCART_HTTP_SERVER%/}")" \
        --language en-gb \
        --db_driver mysqli \
        --db_hostname "$OPENCART_DB_HOST" \
        --db_username "$OPENCART_DB_USER" \
        --db_password "$OPENCART_DB_PASSWORD" \
        --db_database "$OPENCART_DB_NAME" \
        --db_port "${OPENCART_DB_PORT:-3306}" \
        --db_prefix "$OPENCART_DB_PREFIX" 2>&1)"
    printf '%s\n' "$install_output"

    if ! printf '%s\n' "$install_output" | grep -q '^SUCCESS!'; then
        echo "OpenCart installation failed" >&2
        exit 1
    fi
fi

php /usr/local/bin/render-opencart-config
rm -rf /var/www/html/install

mkdir -p \
    system/storage/cache \
    system/storage/download \
    system/storage/logs \
    system/storage/marketplace \
    system/storage/session \
    system/storage/upload \
    image/catalog

# The image directory is a persistent volume, so refresh versioned storefront
# assets from the immutable extension overlay on every container start.
cp -a extension/noveraile/image/. image/

# Generated thumbnails live in the persistent image volume. Remove only this
# extension's cache so stale or partial files are rebuilt from the originals.
if [ -d /var/www/html/image/cache/catalog/noveraile ]; then
    find /var/www/html/image/cache/catalog/noveraile -type f -delete
    find /var/www/html/image/cache/catalog/noveraile -depth -type d -empty -delete
fi

# Keep the module registration and its OpenCart events healthy after both a
# fresh installation and future container replacements.
if [ "$noveraile_seed_demo" = "1" ]; then
    NOVERAILE_WITH_DEMO_DATA=1 php /usr/local/bin/bootstrap-noveraile.php
elif ! timeout --kill-after=5s 30s env NOVERAILE_WITH_DEMO_DATA=0 php /usr/local/bin/bootstrap-noveraile.php; then
    echo "NOVERAILE registration refresh timed out; keeping the existing registration" >&2
fi

# Import the supplier catalog. Writing the products is quick and must surface
# its errors in the deployment log, so it runs in the foreground; fetching a few
# thousand photographs is not allowed to hold up the storefront and continues in
# the background, resuming from whatever the previous container already stored.
if [ "${NOVERAILE_IMPORT_CATALOG:-1}" = "1" ]; then
    if timeout --kill-after=30s "${NOVERAILE_IMPORT_TIMEOUT:-600}s" \
        php /usr/local/bin/noveraile-import-catalog --if-needed --no-images; then
        php /usr/local/bin/noveraile-import-catalog --images-only \
            --budget="${NOVERAILE_IMPORT_IMAGE_BUDGET:-3600}" \
            >> system/storage/logs/noveraile-catalog.log 2>&1 &
    else
        echo "Catalog import failed; the storefront keeps its current catalog" >&2
    fi
fi

chown -R www-data:www-data config.php admin/config.php image/catalog/noveraile
chown www-data:www-data \
    image image/catalog \
    system/storage system/storage/cache system/storage/download \
    system/storage/logs system/storage/marketplace system/storage/session \
    system/storage/upload
chmod 640 config.php admin/config.php

exec docker-php-entrypoint "$@"
