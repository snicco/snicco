<?php


	declare( strict_types = 1 );

	use Tests\stubs\TestApp;
	use WPEmerge\Contracts\RequestInterface;


	$router = TestApp::route();
	$router->get( 'admin.php/bar', function ( RequestInterface $request, string $page ) {

		return strtoupper($page);

	});

	$router->get( 'admin.php/foo', function ( RequestInterface $request, string $page ) {

		return strtoupper($page);

	})->name('foo');

	$router->post( 'biz', function ( RequestInterface $request, string $page ) {

		return strtoupper($page);

	});




