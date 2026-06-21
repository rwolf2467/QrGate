# QrGate — all-in-one image.
#
# Runs the Python/Quart backend, PHP-FPM and nginx together in a single
# container, supervised by supervisor. The PHP frontend talks to the backend
# over localhost (127.0.0.1:1654) inside the container, so only port 80 needs
# to be published.
#
# This is the "one image / one stack" option. For a two-container split (better
# for independent scaling/restarts) use docker-compose.yml instead.

# --- Stage 1: PHP (Composer) dependencies --------------------------------- #
FROM composer:2 AS vendor
WORKDIR /app
COPY frontend/composer.json ./
RUN composer install --no-dev --no-interaction --no-progress --ignore-platform-reqs

# --- Stage 2: runtime ----------------------------------------------------- #
FROM php:8.2-fpm

# System packages: nginx + supervisor to run the services, python venv for the
# backend. ext-curl is already bundled/enabled in the official PHP image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx supervisor python3 python3-venv python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Backend Python deps in an isolated venv (avoids PEP 668 / system pip clashes).
ENV VENV=/opt/venv
RUN python3 -m venv $VENV
ENV PATH="$VENV/bin:$PATH" \
    PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1

COPY backend/requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir -r /tmp/requirements.txt

# App code.
WORKDIR /app
COPY backend/  /app/backend/
COPY frontend/ /app/frontend/
COPY --from=vendor /app/vendor /app/frontend/vendor

# Service config for the combined image.
COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/php-fpm.conf     /usr/local/etc/php-fpm.d/zz-qrgate.conf
COPY docker/uploads.ini      /usr/local/etc/php/conf.d/qrgate-uploads.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /app/backend/data /app/backend/codes \
    && chown -R www-data:www-data /app

# Backend runs without the dev reloader; frontend reaches it on localhost.
ENV QRGATE_RELOAD=0 \
    QRGATE_PORT=1654 \
    QRGATE_API_BASE_URL=http://127.0.0.1:1654/

EXPOSE 80

# Persist runtime data and generated tickets/QR codes.
VOLUME ["/app/backend/data", "/app/backend/codes"]

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
