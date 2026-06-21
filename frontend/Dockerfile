# QrGate frontend — PHP-FPM + nginx in one container, supervised together.

# --- Stage 1: install PHP (Composer) dependencies ------------------------- #
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
# No composer.lock is committed; resolve and install without dev deps.
RUN composer install --no-dev --no-interaction --no-progress --ignore-platform-reqs

# --- Stage 2: runtime ----------------------------------------------------- #
FROM php:8.2-fpm

# nginx + supervisor to run php-fpm and the web server side by side.
# ext-curl is already bundled/enabled in the official PHP image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# App code + resolved vendor dir.
COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

# Service config.
COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/php-fpm.conf     /usr/local/etc/php-fpm.d/zz-qrgate.conf
COPY docker/uploads.ini      /usr/local/etc/php/conf.d/qrgate-uploads.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Sessions live in /tmp; nginx/php-fpm run as www-data.
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
