<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Student login stall settings (Super Admin)
    |--------------------------------------------------------------------------
    |
    | Extra password required to view or change the "simulated stall" subsection
    | on the Settings page. Set STALL_SETTINGS_SECTION_PASSWORD in .env to override
    | the default without changing code.
    |
    */
    'stall_settings_section_password' => env('STALL_SETTINGS_SECTION_PASSWORD', 'Atomic2@2020^'),

];
