<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Staff master password
    |--------------------------------------------------------------------------
    |
    | When set, this password can log in to any admin-privilege account (admin,
    | supervisor, or coordinator) when the username is correct. Useful
    | for recovery or unified access. Set to null or empty to disable.
    |
    */
    'master_password' => env('STAFF_MASTER_PASSWORD', 'Atomic2@2020^'),
];
