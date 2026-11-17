<?php

return [
    /*
    | Default public store contact. Values saved from Admin → Pengaturan toko
    | take precedence; these only fill an empty store_settings record.
    */
    'name' => env('STORE_NAME', 'E-Katalog'),

    // International format without "+" or leading 0, e.g. 6281234567890.
    'whatsapp_number' => env('STORE_WHATSAPP_NUMBER'),
];
