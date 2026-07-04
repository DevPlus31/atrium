# syntax=docker/dockerfile:1

# ==============================================================================
# Atrium production image (context = repo root)
#
#   docker build -f docker/prod/Dockerfile -t atrium-app .
#
# Stages:
#   vendor -> composer install (no scripts, cache-friendly: only composer files)
#   assets -> php + bun; regenerates optimized autoloader with the real app
#             source, runs `bun run build` (wayfinder shells out to artisan)
#   app    -> FrankenPHP runtime (single process serving HTTP + PHP)
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: vendor — PHP dependencies only (composer.json/lock as cache key)
# ------------------------------------------------------------------------------
FROM php:8.5-cli AS vendor

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions intl zip gd exif pcntl sockets bcmath redis pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

# --no-scripts: artisan (package:discover) must not run before the app source
# exists. The autoloader is re-dumped against the real source in the next stage.
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --no-progress

# ------------------------------------------------------------------------------
# Stage 2: assets — needs BOTH php and bun: the wayfinder vite plugin shells
# out to `php artisan wayfinder:generate --with-form` during `bun run build`.
# ------------------------------------------------------------------------------
FROM php:8.5-cli AS assets

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions intl zip gd exif pcntl sockets bcmath redis pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=oven/bun:1 /usr/local/bin/bun /usr/local/bin/bun
RUN ln -s /usr/local/bin/bun /usr/local/bin/bunx

WORKDIR /app

# Dummy environment so artisan can boot during the build without real secrets.
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:bOGeUUf4Qexn3B8bEGGlwLjX955/rZY1sIQD9VBzcQo= \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory: \
    CACHE_STORE=array \
    SESSION_DRIVER=array \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr

# JS dependencies first (cache-friendly).
COPY package.json bun.lock ./
RUN bun install --frozen-lockfile

# Full app source + vendor from the previous stage.
COPY . .
COPY --from=vendor /app/vendor ./vendor

# Now that the real source is present, produce the final optimized autoloader.
# Still --no-scripts: package:discover is not needed at build time (Laravel's
# PackageManifest self-builds on first boot, and the entrypoint re-caches).
RUN composer dump-autoload --optimize --no-scripts \
    && bun run build

# ------------------------------------------------------------------------------
# Stage 3: app — FrankenPHP runtime
# ------------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.5 AS app

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions intl zip gd exif pcntl sockets bcmath redis opcache pdo_pgsql

# Production PHP configuration.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/prod/php.ini /usr/local/etc/php/conf.d/zz-atrium.ini

COPY docker/prod/Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app

# Application code (root-owned, read-only for the runtime user), then build
# artifacts from the earlier stages.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/vendor/composer ./vendor/composer
COPY --from=assets /app/vendor/autoload.php ./vendor/autoload.php
COPY --from=assets /app/public/build ./public/build

COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint.sh

# Non-root: www-data owns only what must be writable at runtime.
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /data/caddy /config/caddy \
    && chown -R www-data:www-data storage bootstrap/cache /data /config

USER www-data

ENV APP_ENV=production \
    APP_DEBUG=false

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/healthz || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
