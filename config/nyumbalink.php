<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded administrator account
    |--------------------------------------------------------------------------
    |
    | Values used by DatabaseSeeder when creating the initial admin user.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@nyumbalink.test'),
        'name' => env('ADMIN_NAME', 'System Administrator'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
