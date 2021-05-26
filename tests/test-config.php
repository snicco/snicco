<?php


	declare( strict_types = 1 );

	return [


		'controllers' => [

			'web' => 'Tests\stubs\Controllers\Web',
			'admin' => 'Tests\stubs\Controllers\Admin',
			'ajax' => 'Tests\stubs\Controllers\Ajax',

		],

		'composers' => [

			'Tests\stubs\ViewComposers',

		],

        'routing' => [
            'definitions' => TESTS_DIR . DS . 'stubs' .DS .  'routes'
        ],

		'views' => [

			TESTS_DIR . DS  . 'views',
			TESTS_DIR . DS  . 'views' . DS . 'subdirectory'

		]



	];