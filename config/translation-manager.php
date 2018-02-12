<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin controller
    |--------------------------------------------------------------------------
    |
    | The controller class used for the translations admin panel.
    |
    */
    'admin_controller_class' => Novius\Backpack\Translation\Manager\Http\Controllers\Admin\TranslationController::class,

    /*
    |--------------------------------------------------------------------------
    | Admin route prefix
    |--------------------------------------------------------------------------
    |
    | The route prefix used for the translations admin panel.
    |
    */
    'route_prefix' => 'translation',

    /*
    |--------------------------------------------------------------------------
    | Available locales
    |--------------------------------------------------------------------------
    |
    | Here you may specify the list of locales that will be available in the translations admin panel.
    | If not set or null, the list will be automatically generated from the dictionaries found.
    |
    */
    // 'locales' => [
    //     'fr',
    //     'en',
    //     'de',
    //     'pt_BR',
    // ],
];
