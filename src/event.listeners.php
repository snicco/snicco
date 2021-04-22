<?php


	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Kernels\HttpKernel;

	return [


		IncomingWebRequest::class => [

			HttpKernel::class . '@filterTemplateInclude'

		],

		IncomingAdminRequest::class => [

			HttpKernel::class . '@filterTemplateInclude'

		],

		AdminBodySendable::class => [

			HttpKernel::class . '@sendBody'

		]



	];