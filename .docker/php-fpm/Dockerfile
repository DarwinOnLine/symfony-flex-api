# See https://hub.docker.com/_/php
ARG PHP_VERSION=7.3
ARG TIMEZONE='UTC'

FROM php:${PHP_VERSION}-fpm

RUN apt-get update && apt-get install -y \
    openssl \
    git \
    unzip \
    libzip-dev \
    zlib1g-dev \
	libicu-dev

RUN set -xe \
    && docker-php-ext-configure \
        intl \
	&& docker-php-ext-install -j$(nproc) \
		intl \
		pdo_mysql \
		bcmath \
		zip \
		mbstring \
	&& pecl install \
	    xdebug \
	&& pecl clear-cache \
	&& docker-php-ext-enable --ini-name 20-xdebug.ini xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/php.ini

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --classmap-authoritative \
	&& composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini
RUN "date"

WORKDIR /srv/symfony

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
