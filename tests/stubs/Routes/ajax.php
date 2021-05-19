<?php

	declare( strict_types = 1 );

	$router = \Tests\stubs\TestApp::route();

	$router->post('foo_action')->handle( function () {

        return 'FOO_ACTION';

    })->name('foo');