<?php

	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Responses\ResponseService;

	use const WPEMERGE_APPLICATION_KEY;
	use const WPEMERGE_REQUEST_KEY;
	use const WPEMERGE_RESPONSE_SERVICE_KEY;
	use const WPEMERGE_VIEW_SERVICE_KEY;

	/**
	 * Provide responses dependencies.
	 *
	 */
	class ResponsesServiceProvider implements ServiceProviderInterface {

		public function register( $container ) {

			$container[ WPEMERGE_RESPONSE_SERVICE_KEY ] = function ( $c ) {

				return new ResponseService( $c[ WPEMERGE_REQUEST_KEY ], $c[ WPEMERGE_VIEW_SERVICE_KEY ] );
			};

			$app = $container[ WPEMERGE_APPLICATION_KEY ];
			$app->alias( 'responses', WPEMERGE_RESPONSE_SERVICE_KEY );
			$app->alias( 'response', function () use ( $app ) {

				return call_user_func_array( [ $app->responses(), 'response' ], func_get_args() );
			} );
			$app->alias( 'output', function () use ( $app ) {

				return call_user_func_array( [ $app->responses(), 'output' ], func_get_args() );
			} );
			$app->alias( 'json', function () use ( $app ) {

				return call_user_func_array( [ $app->responses(), 'json' ], func_get_args() );
			} );
			$app->alias( 'redirect', function () use ( $app ) {

				return call_user_func_array( [ $app->responses(), 'redirect' ], func_get_args() );
			} );
			$app->alias( 'error', function () use ( $app ) {

				return call_user_func_array( [ $app->responses(), 'error' ], func_get_args() );
			} );

		}

		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}
