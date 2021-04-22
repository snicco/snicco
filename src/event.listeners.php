<?php


	use WPEmerge\DynamicHooksFactory;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\RouteMatched;
	use WPEmerge\Events\SendBodySeparately;
	use WPEmerge\Events\StartedLoadingWpAdmin;
	use WPEmerge\Kernels\HttpKernel;
	use WPEmerge\RouteMatcher;

	return [


		IncomingWebRequest::class => [

			RouteMatcher::class . '@handleRequest'

		],

		StartedLoadingWpAdmin::class => [

			DynamicHooksFactory::class . '@handleEvent'

		],

		IncomingAdminRequest::class => [

			RouteMatcher::class . '@handleRequest'

		],

		AdminBodySendable::class => [

			RouteMatcher::class . '@sendAdminBodySeparately'

		],

		RouteMatched::class => [

			HttpKernel::class . '@handle'

		],

		SendBodySeparately::class => [

			HttpKernel::class . '@sendBody'

		],


	];