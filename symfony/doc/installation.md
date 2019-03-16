Installation
============

Sets your SSH keys for JWT (see [bundle doc][LexikJWTAuthenticationBundleDoc]) :

```
$ mkdir -p config/jwt
$ openssl genrsa -out config/jwt/private.pem -aes256 4096
$ openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

Finally, install and set up vendors, and clean production cache :

```
$ composer update
$ bin/console ca:cl --env=prod --no-warmup
```

[LexikJWTAuthenticationBundleDoc]: https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/index.md#getting-started
