<?php


return [

    /*
    |--------------------------------------------------------------------------
    | Messenger Default User Model
    |--------------------------------------------------------------------------
    |
    | This option defines the default User model.
    |
    */

    'user' => [
        'model' => 'App\User'
    ],

    /*
    |--------------------------------------------------------------------------
    | Messenger Pusher Keys
    |--------------------------------------------------------------------------
    |
    | This option defines pusher keys.
    |
    */

    'pusher' => [
        'app_id'     => '',
        'app_key'    => '',
        'app_secret' => '',
        'options' => [
            'cluster'   => '',
            'encrypted' => true
        ]
    ],
];
