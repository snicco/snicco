<?php


    declare(strict_types = 1);

    return [

        'definitions' => ROUTES_DIR,

        'api' => [
            'endpoints' => [
                'test' => 'api-prefix/base',
            ],
        ],

        'trailing_slash' => false,

        'controllers' => [

            'Tests\fixtures\Controllers\Web',
            'Tests\fixtures\Controllers\Admin',
            'Tests\fixtures\Controllers\Ajax',

        ],

        'conditions' => [
            'true' => \Tests\fixtures\Conditions\TrueCondition::class
        ]

    ];
