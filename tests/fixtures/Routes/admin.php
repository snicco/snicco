<?php


	declare( strict_types = 1 );

	use Tests\stubs\TestApp;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Exceptions\InvalidCsrfTokenException;

    $router = TestApp::route();
	$router->get( 'admin.php/bar', function ( Request $request, string $page ) {

		return strtoupper($page). '_ADMIN';

	});
	$router->get( 'admin.php/foo', function ( Request $request, string $page ) {

		return strtoupper($page). '_ADMIN';

	})->name('foo');
	$router->post( 'biz', function ( Request $request, string $page ) {

		return strtoupper($page). '_ADMIN';

	});
    $router->get('admin.php/error', function () {

        throw new InvalidCsrfTokenException();

    });



