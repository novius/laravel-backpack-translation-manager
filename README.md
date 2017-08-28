# Translation Manager for Backpack

This package provides an interface for `Laravel-Backpack` to manage translations stored in database with `spatie/laravel-translation-loader`.

## Installation

#### 1) Register the ServiceProvider

... in `config/app.php` :
```php?start_inline=1
'providers' => [
    // ...
    Novius\Backpack\Translation\Manager\TranslationServiceProvider::class,
]
```

#### 2) Integrate in Backpack's sidebar

In order for the translation manager to be accessible trought the sidebar in the admin panel, you have to overload the view `resources/views/vendor/backpack/base/inc/sidebar.blade.php` and add :

```html
<li>
    <a href="{{ url(config('backpack.base.route_prefix', 'admin').'/'.config('backpack.translation-manager.route_prefix')) }}"><i class="fa fa-cog"></i> <span>{{ trans('translation-manager::app.translation') }}</span></a>
</li>
```

## Usage

@todo

## Todos

- [ ] Handle pluralization
- [x] Extract vendor dictionaries (via namespace)
- [ ] Write Usage section in README