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
cp -a extension/sixmoments/image/. image/

# Keep the module registration and its OpenCart events healthy after both a
# fresh installation and future container replacements.
php /usr/local/bin/bootstrap-sixmoments.php

chown -R www-data:www-data config.php admin/config.php system/storage image
chmod 640 config.php admin/config.php

exec docker-php-entrypoint "$@"
