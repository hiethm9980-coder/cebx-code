<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Insurance Rate Plans
    |--------------------------------------------------------------------------
    |
    | Rate as a percentage (e.g. 1.5 = 1.5%). max_coverage and deductible
    | are in the account's base currency (SAR by default).
    |
    */
    'rates' => [
        'basic'   => ['rate' => 1.5,  'max_coverage' => 50000,   'deductible' => 500],
        'premium' => ['rate' => 2.5,  'max_coverage' => 200000,  'deductible' => 250],
        'full'    => ['rate' => 4.0,  'max_coverage' => 1000000, 'deductible' => 0],
    ],
];
