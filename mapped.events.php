<?php


	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\QueryVarsFilterable;

	return [

		'template_include' => [ IncomingWebRequest::class, 3001 ],
		'request'          => [ QueryVarsFilterable::class, 3001 ],
		'admin_init'       => [ IncomingAdminRequest::class, 3001 ],

	];