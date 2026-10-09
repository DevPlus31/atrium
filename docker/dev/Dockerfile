FROM php:8.5-cli

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions intl zip gd exif pcntl sockets bcmath redis pdo_pgsql xdebug \
    && echo "memory_limit=1G" > /usr/local/etc/php/conf.d/memory-limit.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Keep in step with "packageManager" in package.json.
COPY --from=oven/bun:1.4.2 /usr/local/bin/bun /usr/local/bin/bun

RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Keep in step with "playwright" in package.json: the browser build must
# match the version the browser tests drive.
ARG PLAYWRIGHT_VERSION=1.64.0
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
RUN npm install -g playwright@${PLAYWRIGHT_VERSION} \
    && playwright install --with-deps chromium \
    && rm -rf /var/lib/apt/lists/*

ENV XDEBUG_MODE=off
WORKDIR /var/www/html
