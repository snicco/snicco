<?php


	declare( strict_types = 1 );

	use Tests\stubs\TestApp;
	use WPEmerge\Contracts\RequestInterface;


	$router = TestApp::route();
	$router->get( 'bar', function ( RequestInterface $request, string $page ) {

		return strtoupper($page);

	});

	$router->get( 'foo', function ( RequestInterface $request, string $page ) {

		return strtoupper($page);

	});

	$router->post( 'biz', function ( RequestInterface $request, string $page ) {

		return strtoupper($page);

	});




