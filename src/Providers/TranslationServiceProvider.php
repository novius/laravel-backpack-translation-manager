<?php

namespace Novius\Backpack\Translation\Manager\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Where the route file lives, both inside the package and in the app (if overwritten).
     *
     * @var string
     */
    public $routeFilePath = '/routes/translation-manager.php';

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router $router)
    {
        $appRootDir = dirname(__DIR__, 2);

        // Publishes views/config/language
        $this->publishes([$appRootDir.'/resources/views' => base_path('resources/views')], 'views');
        $this->publishes([$appRootDir.'/config/translation-manager.php' => config_path('translation-manager.php')], 'config');
        $this->publishes([$appRootDir.'/resources/lang' => resource_path('lang/vendor/translation-manager')], 'lang');

        // Loads the views
        $this->loadViewsFrom(resource_path('views/vendor/novius/translation-manager'), 'translation-manager');
        $this->loadViewsFrom($appRootDir.'/resources/views', 'translation-manager');

        // Uses the vendor configuration file as fallback
        $this->mergeConfigFrom($appRootDir.'/config/translation-manager.php', 'translation-manager');

        // Loads the translation
        $this->loadTranslationsFrom($appRootDir.'/resources/lang', 'translation-manager');

        $this->setupRoutes($this->app->router);
    }

    /**
     * Define the routes for the application.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        $appRootDir = dirname(__DIR__, 2);

        // by default, use the routes file provided in vendor
        $routeFilePathInUse = $appRootDir.'/'.$this->routeFilePath;

        // but if there's a file with the same name in routes/backpack, use that one
        if (file_exists(base_path().$this->routeFilePath)) {
            $routeFilePathInUse = base_path().$this->routeFilePath;
        }

        $this->loadRoutesFrom($routeFilePathInUse);
    }
}
