<?php

return [
    // These are labels, not separate authorization roles. All seeded accounts are admins.
    'accounts' => [
        'admin' => [
            'name' => env('SEED_ADMIN_NAME', 'Admin Utama'),
            'email' => env('SEED_ADMIN_EMAIL'),
            'password' => env('SEED_ADMIN_PASSWORD'),
        ],
        'catalog_staff' => [
            'name' => env('SEED_CATALOG_STAFF_NAME', 'Staf Katalog'),
            'email' => env('SEED_CATALOG_STAFF_EMAIL'),
            'password' => env('SEED_CATALOG_STAFF_PASSWORD'),
        ],
        'stock_staff' => [
            'name' => env('SEED_STOCK_STAFF_NAME', 'Staf Stok'),
            'email' => env('SEED_STOCK_STAFF_EMAIL'),
            'password' => env('SEED_STOCK_STAFF_PASSWORD'),
        ],
    ],
];
