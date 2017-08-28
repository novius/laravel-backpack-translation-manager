<?php

/*
|--------------------------------------------------------------------------
| Backpack\PageManager Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are
| handled by the Backpack\PageManager package.
|
*/

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix' => config('backpack.base.route_prefix', 'admin'),
], function () {
    $controller = config('translation-manager.admin_controller_class');
    $prefix = config('translation-manager.route_prefix');
    Route::get($prefix, $controller.'@getIndex');
    Route::post($prefix, $controller.'@postIndex');
});
