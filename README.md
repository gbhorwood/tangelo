# tangelo
basic restful api framework for openswoole

## install swoole

```
sudo apt update
sudo apt install php-dev
sudo pecl install openswoole
<accept defaults>
```

find php.ini for php cli

```
php -i | grep php.ini
```

add module to php.ini with

```
extension=openswoole
```
