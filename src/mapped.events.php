<?php


	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\StartedLoadingWpAdmin;

	return [

		'template_include' => [ [ IncomingWebRequest::class, 3001 ] ],
		'admin_init'       => [ [ StartedLoadingWpAdmin::class, 3001 ] ],

	];