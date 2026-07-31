ARG OPENCART_VERSION=4.1.0.3

FROM composer:2 AS opencart-source

ARG OPENCART_VERSION

RUN apk add --no-cache curl tar
WORKDIR /usr/src/opencart
RUN curl --fail --location --silent --show-error \
        "https://github.com/opencart/opencart/archive/refs/tags/${OPENCART_VERSION}.tar.gz" \
        | tar --extract --gzip --strip-components=1
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --optimize-autoloader

FROM php:8.3-apache-bookworm

ARG OPENCART_VERSION

LABEL org.opencontainers.image.title="6MOMENTS OpenCart" \
      org.opencontainers.image.description="Clean OpenCart with the 6MOMENTS extension overlay" \
      org.opencontainers.image.version="${OPENCART_VERSION}" \
      org.opencontainers.image.source="https://github.com/opencart/opencart"

RUN apt-get update \
    && apt-get install --yes --no-install-recommends \
        curl \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd intl mysqli opcache zip \
    && a2enmod expires headers rewrite \
    && sed -i 's/^Listen 80$/Listen 3000/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=opencart-source /usr/src/opencart/upload/ /var/www/html/
RUN cp .htaccess.txt .htaccess \
    && cp config-dist.php config.php \
    && cp admin/config-dist.php admin/config.php \
    && mkdir -p \
        image/catalog \
        system/storage/cache \
        system/storage/download \
        system/storage/logs \
        system/storage/marketplace \
        system/storage/session \
        system/storage/upload

# The core above stays pristine. Every release replaces only this overlay.
COPY opencart/sixmoments/ /var/www/html/extension/sixmoments/
COPY docker/opencart.ini /usr/local/etc/php/conf.d/opencart.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/render-config.php /usr/local/bin/render-opencart-config
COPY docker/bootstrap-sixmoments.php /usr/local/bin/bootstrap-sixmoments.php
COPY docker/entrypoint.sh /usr/local/bin/sixmoments-entrypoint

RUN find /var/www/html/extension/sixmoments -type f -name '*.php' \
        -exec php -l '{}' ';' \
    && php -l /usr/local/bin/bootstrap-sixmoments.php \
    && sed -i 's/\r$//' /usr/local/bin/sixmoments-entrypoint \
    && chmod +x /usr/local/bin/sixmoments-entrypoint \
    && chown -R www-data:www-data \
        /var/www/html/config.php \
        /var/www/html/admin/config.php \
        /var/www/html/image \
        /var/www/html/system/storage

EXPOSE 3000

HEALTHCHECK --interval=15s --timeout=5s --start-period=90s --retries=5 \
    CMD curl --fail --silent --show-error http://127.0.0.1:3000/ > /dev/null || exit 1

ENTRYPOINT ["sixmoments-entrypoint"]
CMD ["apache2-foreground"]
