#
# NOTE: THIS DOCKERFILE IS GENERATED VIA "apply-templates.sh"
#
# PLEASE DO NOT EDIT IT DIRECTLY.
#

FROM php:8.3.20-fpm-alpine

RUN apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      libxml2-dev \
      linux-headers \
    && apk add --no-cache --virtual .runtime-deps \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
      libzip-dev \
      icu-dev \
    && pecl install \
      redis \
      pcov \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure pcntl \
    && docker-php-ext-install \
      --jobs 4 \
      bcmath \
      gd \
      opcache \
      pdo_mysql \
      sockets \
      zip \
      intl \
      pcntl \
    && docker-php-ext-enable \
      redis \
      pcov \
    && apk del .build-deps \
    && docker-php-source delete \
    && pecl clear-cache

COPY conf.d/php.overrides.ini $PHP_INI_DIR/conf.d
