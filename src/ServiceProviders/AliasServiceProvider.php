<?php


	namespace WPEmerge\ServiceProviders;

	use Contracts\ContainerAdapter;
	use WPEmerge\Application\Application;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\ViewComposers\ViewComposerCollection;
	use WPEmerge\ViewComposers\ViewComposerFactory;

	class AliasServiceProvider implements ServiceProviderInterface {

		public function register( ContainerAdapter $container ) {


			$app = $container[ WPEMERGE_APPLICATION_KEY ];

			$this->applicationAliases($app);
			$this->responseAliases($app);
			$this->routingAliases($app);
			$this->viewAliases($app);
			$this->sessionAliases($app);


		}

		public function bootstrap( ContainerAdapter $container ) {
			//
		}

		private function applicationAliases ( Application $app ) {

			$app->alias( 'app', WPEMERGE_APPLICATION_KEY );


		}

		private function responseAliases (Application $app) {


			$app->alias( 'response_service', WPEMERGE_RESPONSE_SERVICE_KEY );
			$app->alias( 'response', function () use ( $app ) {
				return call_user_func_array( [ $app->response_service(), 'response' ], func_get_args() );
			} );
			$app->alias( 'output', function () use ( $app ) {

				return call_user_func_array( [ $app->response_service(), 'output' ], func_get_args() );
			} );
			$app->alias( 'json', function () use ( $app ) {

				return call_user_func_array( [ $app->response_service(), 'json' ], func_get_args() );
			} );
			$app->alias( 'redirect', function () use ( $app ) {

				return call_user_func_array( [ $app->response_service(), 'redirect' ], func_get_args() );
			} );
			$app->alias( 'error', function () use ( $app ) {

				return call_user_func_array( [ $app->response_service(), 'error' ], func_get_args() );
			} );

		}

		private function routingAliases (Application $app ) {

			$app->alias( 'route', WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY );
			$app->alias( 'routeUrl', WPEMERGE_ROUTING_ROUTER_KEY, 'getRouteUrl' );

		}

		private function viewAliases (Application $app ) {

			$app->alias('addComposer', function () use ( $app ) {

				$composer_collection = $app->resolve(ViewComposerCollection::class);

				$args = func_get_args();

				$composer_collection->addComposer(...$args);


			});
			$app->alias( 'views', WPEMERGE_VIEW_SERVICE_KEY );
			$app->alias( 'view', function () use ( $app ) {

				return call_user_func_array( [ $app->views(), 'make' ], func_get_args() );
			} );
			$app->alias( 'render', function () use ( $app ) {

				$view_as_string = call_user_func_array( [ $app->views(), 'render' ], func_get_args() );

				echo $view_as_string;

			} );
			$app->alias( 'layoutContent', function () use ( $app ) {

				$engine = $app->resolve( WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY );

				echo $engine->getLayoutContent();

			} );

		}

		private function sessionAliases (Application $app ) {

			$app->alias( 'oldInput', WPEMERGE_OLD_INPUT_KEY );
			$app->alias( 'csrf', WPEMERGE_CSRF_KEY );#
			$app->alias( 'flash', WPEMERGE_FLASH_KEY );
		}

	}