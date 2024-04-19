#
# NOTE: THIS DOCKERFILE IS GENERATED VIA "apply-templates.sh"
#
# PLEASE DO NOT EDIT IT DIRECTLY.
#

FROM php:8.2.18-cli-alpine

COPY conf.d/php.overrides.ini $PHP_INI_DIR/conf.d

RUN apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      libxml2-dev \
      linux-headers \
    && apk add --no-cache --virtual .runtime-deps \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
      libzip-dev \
    && pecl install \
      redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg  \
    && docker-php-ext-install \
      bcmath \
      gd \
      opcache \
      pdo_mysql \
      sockets \
      zip \
    && docker-php-ext-enable \
      redis \
    && apk del .build-deps \
    && docker-php-source delete \
    && pecl clear-cache