# Laravel Myanmar Payments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/Laranex/laravel-myanmar-payments)
[![Total Downloads](https://img.shields.io/packagist/dt/Laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/Laranex/laravel-myanmar-payments)

Laravel custom log channel for [Laralog Server](https://github.com/naythukhant/laralog)

This package works together with [Laralog Server](https://github.com/naythukhant/laralog) and you need to set up a log server using 
[Laralog Server](https://github.com/naythukhant/laralog) for using this package.


## Installation

You can install the package via composer:

```bash
composer require laranex/laralog-client
```

## Configuration

```bash
  php artisan vendor:publish --tag="laralog-client"
```

Update your default log channel in .env

```php
LOG_CHANNEL=laralog
```

For usage reference, please refer to [Logging - Laravel](https://laravel.com/docs/master/logging)

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email naythukhant644@gmail.com instead of using the issue tracker.

## Credits

- [Nay Thu Khant](https://github.com/naythukhant)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.