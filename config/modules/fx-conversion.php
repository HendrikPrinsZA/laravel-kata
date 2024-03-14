<?php

return [
    'options' => [
        'script-caching' => [
            'enabled' => true,
            'strategy' => 'monthly', // daily, monthly, yearly, all
        ],
        'global-caching' => [
            'enabled' => true,
            'strategy' => 'all',
        ],
    ],
];
