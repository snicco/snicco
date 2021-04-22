<?php

	use WPEmerge\AjaxShutdownHandler;
	use WPEmerge\DynamicHooksFactory;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\ResponseSent;
	use WPEmerge\Events\RouteMatched;
	use WPEmerge\Events\SendBodySeparately;
	use WPEmerge\Events\StartedLoadingWpAdmin;
	use WPEmerge\Kernels\HttpKernel;
	use WPEmerge\RouteMatcher;

	return [


		IncomingWebRequest::class => [

			RouteMatcher::class . '@handleRequest'

		],

		IncomingAdminRequest::class => [

			RouteMatcher::class . '@handleRequest'

		],

		IncomingAjaxRequest::class => [

			RouteMatcher::class . '@handleRequest'

		],

		StartedLoadingWpAdmin::class => [

			DynamicHooksFactory::class . '@handleEvent'

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

		ResponseSent::class => [

			AjaxShutdownHandler::class . '@shutdownWp'

		]


	];