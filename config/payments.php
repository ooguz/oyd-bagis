<?php

return [
    'driver' => env('PAYMENTS_DRIVER', 'iyzico'),

    'currency' => 'TRY',

    'three_d_threshold_major' => (float) env('IYZI_THREE_D_THRESHOLD', 500.00),

    'preset_amounts_major' => [100, 250, 500, 1000],

    'bank_transfer' => [
        'bank_name' => env('BANK_NAME', 'Örnek Banka'),
        'iban' => env('BANK_IBAN', 'TR00 0000 0000 0000 0000 0000 00'),
        'account_holder' => env('BANK_ACCOUNT_HOLDER', 'Dernek Adı'),
    ],

    'bitcoin' => [
        'address' => env('BITCOIN_ADDRESS', 'bc1qexampleaddressforbtc0000000000'),
    ],

    'features' => [
        'subscriptions' => filter_var(env('FEATURE_SUBSCRIPTIONS', false), FILTER_VALIDATE_BOOLEAN),
    ],
];



