# [PHP](https://hub.docker.com/r/lynxsolutions/php) base image
_A [PHP](https://php.net) base image for [Laravel](https://laravel.com) projects._

Being based on it, this image is a drop-in replacement for the [Official PHP image](https://hub.docker.com/_/php).

## Extensions
The main image comes with some commonly used extensions already installed:
- [`gd --with-jpeg --with-freetype`](https://www.php.net/manual/en/book.image.php)
- [`opcache`](https://www.php.net/manual/en/book.opcache.php)
- [`pdo_mysql`](https://www.php.net/manual/en/ref.pdo-mysql.php)
- [`sockets`](https://www.php.net/manual/en/book.sockets.php)
- [`zip`](https://www.php.net/manual/en/book.zip.php)
- [`redis`](https://github.com/phpredis/phpredis/)
- [`intl`](https://www.php.net/manual/en/book.intl.php) (**starting from `8.2.20` and `8.3.8`**)

The `-xdebug` and `-pcov` suffixed images also come with [Xdebug](https://xdebug.org) and [PCOV](https://github.com/krakjoe/pcov) installed accordingly.

## Ini [overrides](conf.d/php.overrides.ini)
```ini
expose_php = Off
```
