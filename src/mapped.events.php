<?php


	use WPEmerge\Events\IncomingWebRequest;

	return [

		'template_include' => [ [ IncomingWebRequest::class, 3001 ] ],
		// 'request'          => [ QueryVarsFilterable::class, 3001 ],
		// 'admin_init'       => [ [ DynamicAdminHooks::class, 3001 ] ],

	];