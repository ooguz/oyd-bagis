<?php

return [
    'driver' => env('PAYMENTS_DRIVER', 'iyzico'),

    'currency' => 'TRY',

    // checkout: hosted iyzico iframe, direct: collect card on our form and call API
    'flow' => env('PAYMENTS_FLOW', 'checkout'),

    'three_d_threshold_major' => (float) env('IYZI_THREE_D_THRESHOLD', 500.00),

    'preset_amounts_major' => [100, 250, 500, 1000],

    'bank_transfer' => [
        'bank_name' => env('BANK_NAME', 'TÜRKİYE İŞ BANKASI'),
        'iban' => env('BANK_IBAN', 'TR47 0006 4000 0011 0770 7443 82'),
        'account_holder' => env('BANK_ACCOUNT_HOLDER', 'Özgür Yazılım Derneği'),
    ],

    'bitcoin' => [
        'address' => env('BITCOIN_ADDRESS', '1ozgurDzanWtMofFPLibQaRWGbWxRY74E'),
    ],

    'features' => [
        'subscriptions' => filter_var(env('FEATURE_SUBSCRIPTIONS', false), FILTER_VALIDATE_BOOLEAN),
    ],
];



