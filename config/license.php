<?php

return [

    /*
    |--------------------------------------------------------------------------
    | License Server URL
    |--------------------------------------------------------------------------
    | The base URL of your Softtrill license verification server.
    | The middleware will POST to: {server_url}/api/license/verify
    */
    'server_url' => env('LICENSE_SERVER_URL', 'https://softtrill.com'),

    /*
    |--------------------------------------------------------------------------
    | License Secret Salt
    |--------------------------------------------------------------------------
    | A shared secret between the LMS and softtrill.com used to sign the
    | server fingerprint. Keep this private — never expose it to clients.
    | Generate a strong random string and set the SAME value on softtrill.com.
    */
    'secret_salt' => env('LICENSE_SECRET_SALT', 'change-this-salt-in-env'),

];
