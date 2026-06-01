# ─── BASE STAGE ─────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS base

LABEL maintainer="OIMP Platform <devops@oimp.io>"
LABEL org.opencontainers.image.source="https://github.com/oimp/platform"

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME="/tmp/composer" \
    PHP_MEMORY_LIMIT=256M \
    PHP_UPLOAD_LIMIT=64M

# System dependencies
RUN apk add --no-cache \
        acl \
        fcgi \
        file \
        gettext \
        git \
        gnu-libiconv \
        icu-dev \
        libzip-dev \
        linux-headers \
        oniguruma-dev \
        openssl-dev \
        postgresql-dev \
        rabbitmq-c \
        rabbitmq-c-dev \
        redis \
        unzip \
        zip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    # PHP Extensions
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        intl \
        opcache \
        pdo_pgsql \
        sysvsem \
        zip \
    # PECL Extensions
    && pecl install amqp redis \
    && docker-php-ext-enable amqp redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ─── DEVELOPMENT STAGE ──────────────────────────────────────────────────────
FROM base AS dev

RUN apk add --no-cache \
        bash \
        make \
        nodejs \
        npm \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Entrypoint handles vendor install, JWT keygen, cache clear and migrations
# at container start — the source volume overwrites anything built into the image.
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

USER www-data
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ─── PRODUCTION BUILD STAGE ─────────────────────────────────────────────────
FROM base AS builder

COPY --chown=www-data:www-data . .

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --classmap-authoritative \
    && composer clear-cache

# ─── PRODUCTION STAGE ───────────────────────────────────────────────────────
FROM base AS prod

ENV APP_ENV=prod

RUN apk add --no-cache opcache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf
# 100- loads after 99-custom.ini, so prod opcache settings win over dev defaults
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/100-opcache.ini

COPY --from=builder --chown=www-data:www-data /var/www/html .

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

USER www-data
EXPOSE 9000

HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD REDIRECT_STATUS=true SCRIPT_FILENAME=/dev/null REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

# ─── WORKER STAGE ───────────────────────────────────────────────────────────
# One process per container — Docker Compose handles scaling.
# supervisor is unnecessary here; supervisord.conf is kept for reference only.
FROM dev AS worker

USER www-data
CMD ["php", "bin/console", "messenger:consume", "async", "async_high", \
     "--time-limit=3600", "--memory-limit=128M", "-vv"]
