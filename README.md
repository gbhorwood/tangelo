# tangelo
A basic restful api framework for openswoole.

## what tangelo is
tangelo is a feature-lite framework for building restful apis on top of [openswoole](https://openswoole.com/).
It is designed for use on internal projects at [fruitbat studios](https://fruitbat.studio) and kludgetastic implementations.

## creating a tangelo project
Creating a new tangelo project is a three step process:

- Install openswoole
- Create an empty composer project
- Install tangelo via composer
- Create the default structure of the project with the `scaffold.php` command

## Install openswoole
Openswool is an extension for PHP delivered through [pecl](https://pecl.php.net/).
>>>>>>> develop

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
