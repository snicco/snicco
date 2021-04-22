<?php


	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Kernels\HttpKernel;

	return [


		IncomingWebRequest::class => [

			HttpKernel::class . '@handle'

		],

		IncomingAdminRequest::class => [

			HttpKernel::class . '@handle'

		],

		AdminBodySendable::class => [

			HttpKernel::class . '@sendBody'

		]



	];