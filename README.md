# :package_description

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stechstudio/laravel-hubspot.svg?style=flat-square)](https://packagist.org/packages/stechstudio/laravel-hubspot)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/stechstudio/laravel-hubspot/run-tests?label=tests)](https://github.com/stechstudio/laravel-hubspot/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/stechstudio/laravel-hubspot/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/stechstudio/laravel-hubspot/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/stechstudio/laravel-hubspot.svg?style=flat-square)](https://packagist.org/packages/stechstudio/laravel-hubspot)
<!--delete-->
---
This repo can be used to scaffold a Laravel package. Follow these steps to get started:

1. Press the "Use this template" button at the top of this repo to create a new repo with the contents of this skeleton.
2. Run "php ./configure.php" to run a script that will replace all placeholders throughout all the files.
3. Have fun creating your package.
4. If you need help creating a package, consider picking up our <a href="https://laravelpackage.training">Laravel Package Training</a> video course.
---
<!--/delete-->
This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require stechstudio/laravel-hubspot
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-hubspot-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-hubspot-views"
```

## Usage

```php
$variable = new VendorName\Skeleton();
echo $variable->echoPhrase('Hello, VendorName!');
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
