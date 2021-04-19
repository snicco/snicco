<?php

namespace WPEmerge\Csrf;

use WPEmerge\Contracts\ServiceProviderInterface;

/**
 * Provide CSRF dependencies.
 *
 * @codeCoverageIgnore
 */
class CsrfServiceProvider implements ServiceProviderInterface {
	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$container[ WPEMERGE_CSRF_KEY ] = function () {
			return new Csrf();
		};

		$container[ CsrfMiddleware::class ] = function ( $c ) {
			return new CsrfMiddleware( $c[ WPEMERGE_CSRF_KEY ] );
		};

		$app = $container[ WPEMERGE_APPLICATION_KEY ];
		$app->alias( 'csrf', WPEMERGE_CSRF_KEY );
	}

	/**
	 * {@inheritDoc}
	 */
	public function bootstrap( $container ) {
		// Nothing to bootstrap.
	}
}
