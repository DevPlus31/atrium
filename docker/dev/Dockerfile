FROM php:8.5-cli

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions intl zip gd exif pcntl sockets bcmath redis xdebug \
    && echo "memory_limit=1G" > /usr/local/etc/php/conf.d/memory-limit.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
RUN npm install -g playwright@1.61.1 \
    && playwright install --with-deps chromium \
    && rm -rf /var/lib/apt/lists/*

ENV XDEBUG_MODE=off
WORKDIR /var/www/html
