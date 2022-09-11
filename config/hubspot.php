<?php
return [
    'access_token' => env('HUBSPOT_ACCESS_TOKEN'),

    'contacts' => [
        'include_properties' => ['firstname','lastname','email'],
        'include_associations' => ['companies','deals','tickets'],
    ],

    'companies' => [
        'include_properties' => ['domain','name','phone'],
        'include_associations' => ['companies','contacts','deals','tickets'],
    ],

    'deals' => [
        'include_properties' => ['dealname','amount','closedate','pipeline','dealstage'],
        'include_associations' => ['companies','contacts','deals','tickets'],
    ],

    'products' => [
        'include_properties' => ['name','description','price'],
        'include_associations' => ['companies','contacts','deals','tickets'],
    ],

    'tickets' => [
        'include_properties' => ['content','subject'],
        'include_associations' => ['companies','contacts','deals','tickets'],
    ],

    'line_items' => [
        'include_properties' => ['quantity','amount','price'],
        'include_associations' => ['companies','contacts','deals','tickets'],
    ],

    'quotes' => [
        'include_properties' => [],
        'include_associations' => ['companies','contacts','deals','tickets'],
    ]
];
