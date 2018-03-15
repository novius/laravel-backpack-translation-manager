# Translation Manager for Backpack

This package provides an interface for `Laravel-Backpack` to manage translations stored in database with `spatie/laravel-translation-loader`.

## Requirements

Requires the `intl` PHP extension (http://php.net/intl).

## Installation

You can install the package via composer:

```sh
composer require novius/laravel-backpack-translation-manager
```

#### Configure the translation loader

The package `spatie/laravel-translation-loader` is automatically installed (composer dependency), but you have to configure it manually.

Please follow these instructions: https://github.com/spatie/laravel-translation-loader#installation

#### Register the service provider

Only for Laravel <= 5.4 :

... in `config/app.php` :
```php?start_inline=1
'providers' => [
    // ...
    Novius\Backpack\Translation\Manager\Providers\TranslationServiceProvider::class,
]
```

#### [Optional] Integrate in Backpack's sidebar

In order for the translation manager to be accessible trought the sidebar in the admin panel, you have to overload the view `resources/views/vendor/backpack/base/inc/sidebar.blade.php` and add :

```html
<li>
    <a href="{{ url(config('backpack.base.route_prefix', 'admin').'/'.config('translation-manager.route_prefix')) }}"><i class="fa fa-cog"></i> <span>{{ trans('translation-manager::crud.sidebar_title') }}</span></a>
</li>
```

## Usage

@todo

## Todos

- [ ] Handle pluralization
- [x] Extract vendor dictionaries (via namespace)
- [ ] Write Usage section in README