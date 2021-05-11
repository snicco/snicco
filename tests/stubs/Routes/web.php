<?php


	declare( strict_types = 1 );

	$router = \Tests\stubs\TestApp::route();

	$router->get('foo', function () {

		return 'foo';

	});