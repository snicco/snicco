<?php


	declare( strict_types = 1 );

	use Tests\stubs\TestApp;

	$router = TestApp::route();

	$router->get( '/{path:.+}' )->where( 'admin', 'test' )
	       ->handle( function () {

		       return 'foo';

	       } );
