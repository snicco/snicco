<?php


	declare( strict_types = 1 );

	use Tests\stubs\TestApp;
    use Snicco\Http\Psr7\Request;
    use Snicco\Session\Exceptions\InvalidCsrfTokenException;

    $router = TestApp::route();
	$router->get( 'admin.php/bar', function ( Request $request ) {

		return strtoupper($request->input('page')). '_ADMIN';

	});

	$router->get( 'admin.php/foo', function ( Request $request ) {

		return strtoupper($request->input('page')). '_ADMIN';

	})->name('foo');

	$router->post( 'biz', function ( Request $request ) {

		return strtoupper($request->input('page')). '_ADMIN';

	});

    $router->get('admin.php/error', function () {

        throw new InvalidCsrfTokenException();

    });
    $router->redirect('index.php', '/foo');


