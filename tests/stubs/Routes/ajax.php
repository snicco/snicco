<?php

	declare( strict_types = 1 );

	$router = \Tests\stubs\TestApp::route();

	$router->post('/{path:.+}')->where( 'ajax', 'test', true, true )->handle( function () {

		return 'foo';


	});