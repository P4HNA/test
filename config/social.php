<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Social Links
    |--------------------------------------------------------------------------
    |
    | Destination URLs for the "follow us" links shown in the site footers
    | (homepage footer and classroom footer). These are public marketing
    | links, not credentials, so they're kept separate from services.php.
    |
    | Telegram is not a link: both footers open a QR-code popup instead
    | (resources/views/partials/_telegram-qr-dialog.blade.php, image at
    | public/images/telegram-qr.jpg). The "telegram" key below is currently
    | unused by the views and kept only for a future direct link.
    |
    */

    'facebook' => env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/yoeurnpiseth1'),

    'instagram' => env('SOCIAL_INSTAGRAM_URL', 'https://www.instagram.com/piseth.design/'),

    'telegram' => env('SOCIAL_TELEGRAM_URL'),

];
