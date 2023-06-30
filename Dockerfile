FROM php:8.1-fpm-alpine as vendor
WORKDIR /app

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY composer.* symfony.* ./
RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress && \
    composer clear-cache

# Install dependencies only when needed
FROM node:18-alpine as deps
WORKDIR /app

COPY --from=vendor /app/vendor /app/vendor
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile --ignore-scripts

# Rebuild the source code only when needed
FROM node:18-alpine AS builder
WORKDIR /app

COPY --from=deps /app/node_modules ./node_modules
COPY package.json webpack.config.js yarn.lock ./
COPY assets ./assets

RUN mkdir -p public && \
    yarn build

FROM php:8.1-fpm-alpine

ARG APP_VERSION=dev
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_VERSION="${APP_VERSION}" \
    TZ="Europs/Paris"

EXPOSE 80
WORKDIR /app

# Install dependencies
RUN apk add --no-cache \
    bash \
    icu-data-full \
    icu-libs \
    git \
    libzip \
    nginx \
    supervisor \
    tzdata && \
    echo "Europe/Paris" > /etc/timezone && \
    #Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    # Reduce layer size
    rm -rf /var/cache/apk/* /tmp/*

# PHP Extensions
ENV PHPIZE_DEPS \
    autoconf \
    g++ \
    gcc \
    icu-dev \
    libzip-dev \
    make \
    zlib-dev

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS && \
    docker-php-ext-install -j$(nproc) exif intl opcache zip && \
    pecl install apcu && \
    docker-php-ext-enable apcu && \
    apk del .build-deps && \
    rm -rf /var/cache/apk/* /tmp/*

# Config
COPY docker/nginx.conf /etc/nginx/
COPY docker/php.ini /usr/local/etc/php/php.ini
COPY docker/pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY . /app
COPY --from=vendor /app/vendor /app/vendor
COPY --from=builder /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

RUN mkdir -p /run/php var/cache public/build && \
    composer dump-autoload --classmap-authoritative --no-dev && \
    APP_ENV=prod composer dump-env prod && \
    rm -rf var/cache && \
    APP_ENV=prod bin/console cache:clear --no-warmup && \
    APP_ENV=prod bin/console cache:warmup && \
    APP_ENV=prod composer run-script --no-dev post-install-cmd && \
    chown -R www-data:www-data var public/build && \
    # Reduce container size
    rm -rf .git docker assets /root/.composer /tmp/*
