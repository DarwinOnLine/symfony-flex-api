#!/usr/bin/env bash

set -e
trap 'echo "Some errors occurred, please verify" && exec docker-php-entrypoint "$@"' ERR

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
	mkdir -p var/cache var/log var/sessions

    if [ -f .env.local ] ; then
        source .env.local
    fi

	if [ "$APP_ENV" != 'prod' ]; then
		composer install --prefer-dist --no-progress --no-suggest --no-interaction 2>&1
    else
        composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader 2>&1
        bin/console assets:install
	fi

	if [ ! -f config/jwt/private.pem ] ; then
		openssl genrsa -passout pass:$JWT_PASSPHRASE -out config/jwt/private.pem -aes256 4096
		openssl rsa -passin pass:$JWT_PASSPHRASE -pubout -in config/jwt/private.pem -out config/jwt/public.pem
	fi

	# Permissions hack because setfacl does not work on Mac and Windows
	chown -R www-data var
fi

exec docker-php-entrypoint "$@"
