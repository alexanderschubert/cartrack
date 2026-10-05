# syntax=docker/dockerfile:1.7

FROM node:22 AS node

FROM php:8.5-cli AS build
WORKDIR /app
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/bin/npm /usr/local/bin/npm
COPY --from=node /usr/local/bin/npx /usr/local/bin/npx
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN apt-get update && apt-get install -y --no-install-recommends curl git unzip libicu-dev libpq-dev libzip-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*
RUN curl -fsSL https://get.pnpm.io/install.sh | env PNPM_HOME=/usr/local/bin PNPM_VERSION=11.25.0 SHELL=/bin/sh sh -
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json package.json pnpm-workspace.yaml pnpm-lock.yaml .npmrc ./
RUN composer update --no-dev --no-interaction --prefer-dist --no-progress --no-scripts \
    && pnpm install --frozen-lockfile
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= php artisan package:discover --ansi \
    && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= php artisan wayfinder:generate \
    && pnpm run build

FROM php:8.5-fpm-alpine AS runtime
RUN apk add --no-cache nginx supervisor icu-libs libpq libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev postgresql-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && mkdir -p /run/nginx /var/www/storage/app/private /var/www/storage/app/public \
    && addgroup -g 10001 cartrack \
    && adduser -D -u 10001 -G cartrack cartrack \
    && sed -i 's/^user = www-data/user = cartrack/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^group = www-data/group = cartrack/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www
COPY --from=build --chown=cartrack:cartrack /app /var/www
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-cartrack.ini

RUN chown -R cartrack:cartrack storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -q --spider http://127.0.0.1:8080/up || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
