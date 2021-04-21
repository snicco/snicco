<?php


	use Illuminate\Routing\Router;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingWebRequest;

	return [


		IncomingWebRequest::class => [

			Router::class . '@loadWebRoutes'

		],

		IncomingAdminRequest::class => [

			Router::class . '@loadAdminRoutes'

		],

		Inco::class => [

			Router::class . '@loadAdminRoutes'

		],




	];