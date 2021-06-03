<?php


	declare( strict_types = 1 );

	return [

        'app_key' => TEST_APP_KEY,

		'controllers' => [

			'web' => 'Tests\fixtures\Controllers\Web',
			'admin' => 'Tests\fixtures\Controllers\Admin',
			'ajax' => 'Tests\fixtures\Controllers\Ajax',

		],

		'composers' => [

			'Tests\fixtures\ViewComposers',

		],

        'routing' => [

            'definitions' => ROUTES_DIR
        ],

		'views' => [

			VIEWS_DIR,
            VIEWS_DIR . DS . 'subdirectory'

		]



	];