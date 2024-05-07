# [PHP](https://hub.docker.com/_/php) but spicier 🌶️
A base `php` image mainly for [`laravel`](https://laravel.com/).

## Extensions
The main image comes with some commonly used extensions already installed:
- `gd --with-jpeg --with-freetype`
- `opcache`
- `pdo_mysql`
- `sockets`
- `zip`
- `redis`

The `-xdebug` and `-pcov` suffixed images also come with [Xdebug](https://xdebug.org) and [pcov](https://github.com/krakjoe/pcov) installed accordingly.

## Ini [overrides](conf.d/php.overrides.ini)
```ini
expose_php = Off
```
