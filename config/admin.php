<?php

return [

    /*
    | Optional private domain for the admin area, e.g. admin.example.com.
    | Leave empty to keep the admin area on your normal domain.
    */
    'domain' => env('ADMIN_DOMAIN'),

    /*
    | IPs or CIDR ranges allowed into the admin area, separated by commas.
    | Example: ADMIN_ALLOWED_IPS="127.0.0.1,::1,203.0.113.10,198.51.100.0/24"
    | Empty means NOBODY is allowed (fails closed).
    */
    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_ALLOWED_IPS', ''))
    ))),

    /*
    | Biggest video file the admin panel accepts, in megabytes.
    */
    'video_max_mb' => (int) env('ADMIN_VIDEO_MAX_MB', 2048),

    /*
    | The admin account created by "php artisan db:seed".
    | The password is only used when that account is first created: it never
    | overwrites the password of an admin that already exists. With no
    | ADMIN_SEED_PASSWORD set, the seeder skips creating the admin (it prints
    | a warning) rather than falling back to a guessable default.
    */
    'seed_email' => env('ADMIN_SEED_EMAIL', 'admin@piseth.design'),
    'seed_password' => env('ADMIN_SEED_PASSWORD'),

];
