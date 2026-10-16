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
        'email' => env('ADMIN_EMAIL', 'admin@nyumbalink.com'),
        'name' => env('ADMIN_NAME', 'System Administrator'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF receipts
    |--------------------------------------------------------------------------
    |
    | Optional contact line (phone or email) printed in the footer of PDF
    | receipts. When empty, the line is omitted.
    |
    */

    'receipt' => [
        'contact' => env('RECEIPT_CONTACT'),
    ],

];
