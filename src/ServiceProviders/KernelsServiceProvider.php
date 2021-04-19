<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Csrf\CsrfMiddleware;
	use WPEmerge\Flash\FlashMiddleware;
	use WPEmerge\Input\OldInputMiddleware;
	use WPEmerge\Kernels\HttpKernel;
	use WPEmerge\Middleware\SubstituteModelBindings;
	use WPEmerge\Middleware\UserCanMiddleware;
	use WPEmerge\Middleware\UserLoggedInMiddleware;
	use WPEmerge\Middleware\UserLoggedOutMiddleware;

	use const WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY;
	use const WPEMERGE_APPLICATION_KEY;
	use const WPEMERGE_CONFIG_KEY;
	use const WPEMERGE_CONTAINER_ADAPTER;
	use const WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY;
	use const WPEMERGE_HELPERS_HANDLER_FACTORY_KEY;
	use const WPEMERGE_REQUEST_KEY;
	use const WPEMERGE_RESPONSE_SERVICE_KEY;
	use const WPEMERGE_ROUTING_ROUTER_KEY;
	use const WPEMERGE_VIEW_SERVICE_KEY;
	use const WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY;

	/**
	 * Provide old input dependencies.
	 *
	 */
	class KernelsServiceProvider implements ServiceProviderInterface {

		use ExtendsConfigTrait;


		public function register( $container ) {

			$this->extendConfig( $container, 'middleware', [
				'flash'           => FlashMiddleware::class,
				'old_input'       => OldInputMiddleware::class,
				'csrf'            => CsrfMiddleware::class,
				'user.logged_in'  => UserLoggedInMiddleware::class,
				'user.logged_out' => UserLoggedOutMiddleware::class,
				'user.can'        => UserCanMiddleware::class,
			] );

			$this->extendConfig( $container, 'middleware_groups', [
				'wpemerge' => [
					'flash',
					'old_input',
				],
				'global'   => [],
				'web'      => [],
				'ajax'     => [],
				'admin'    => [],
			] );

			$this->extendConfig( $container, 'middleware_priority', [] );

			$container[ WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY ] = function ( &$c ) {

				$kernel = new HttpKernel(

					$c[ WPEMERGE_CONTAINER_ADAPTER ],
					$c[ WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY ],
					$c[ WPEMERGE_HELPERS_HANDLER_FACTORY_KEY ],
					$c[ WPEMERGE_RESPONSE_SERVICE_KEY ],
					$c[ WPEMERGE_REQUEST_KEY ],
					$c[ WPEMERGE_ROUTING_ROUTER_KEY ],
					$c[ WPEMERGE_VIEW_SERVICE_KEY ],
					$c[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ]

				);

				$config = $c[ WPEMERGE_CONFIG_KEY ];

				$kernel->setMiddleware( $config['middleware'] );

				$config['middleware_groups']['global'] = array_merge(
					[SubstituteModelBindings::class],
					$config['middleware_groups']['global']
				);

				// Make sure it always comes first.
				array_unshift( $config['middleware_priority'] , SubstituteModelBindings::class );

				$kernel->setMiddlewareGroups( $config['middleware_groups'] );
				$kernel->setMiddlewarePriority( $config['middleware_priority'] );

				$c[ WPEMERGE_CONFIG_KEY ] = $config;

				return $kernel;

			};

			$app = $container[ WPEMERGE_APPLICATION_KEY ];

			$app->alias( 'run', function () use ( $app ) {

				$kernel = $app->resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

				return call_user_func_array( [ $kernel, 'run' ], func_get_args() );
			} );
		}


		public function bootstrap( $container ) {

			// Nothing to bootstrap.

		}

	}
