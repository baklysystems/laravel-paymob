# Laravel PayMob

A Laravel online payment gateway.

## Installation

Require via composer

```bash
$ composer require baklysystems/laravel-paymob
```

In `config/app.php` file

```php
'providers' => [
    ...
    BaklySystems\PayMob\PayMobServiceProvider::class,
    ...
];

'aliases' => [
    ...
    'PayMob' => BaklySystems\PayMob\Facades\PayMob::class,
    ...
];
```
