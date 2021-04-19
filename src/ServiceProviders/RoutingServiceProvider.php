<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Routing\Conditions\AdminCondition;
	use WPEmerge\Routing\Conditions\AjaxCondition;
	use WPEmerge\Routing\Conditions\ConditionFactory;
	use WPEmerge\Routing\Conditions\CustomCondition;
	use WPEmerge\Routing\Conditions\ModelCondition;
	use WPEmerge\Routing\Conditions\MultipleCondition;
	use WPEmerge\Routing\Conditions\NegateCondition;
	use WPEmerge\Routing\Conditions\PostIdCondition;
	use WPEmerge\Routing\Conditions\PostSlugCondition;
	use WPEmerge\Routing\Conditions\PostStatusCondition;
	use WPEmerge\Routing\Conditions\PostTemplateCondition;
	use WPEmerge\Routing\Conditions\PostTypeCondition;
	use WPEmerge\Routing\Conditions\QueryVarCondition;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Routing\RouteBlueprint;
	use WPEmerge\Routing\Router;

	// use const WPEMERGE_APPLICATION_KEY;
	// use const WPEMERGE_HELPERS_HANDLER_FACTORY_KEY;
	// use const WPEMERGE_ROUTING_CONDITION_TYPES_KEY;
	// use const WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY;
	// use const WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY;
	// use const WPEMERGE_ROUTING_ROUTER_KEY;
	// use const WPEMERGE_VIEW_SERVICE_KEY;

	/**
	 * Provide routing dependencies
	 *
	 */
	class RoutingServiceProvider implements ServiceProviderInterface {

		use ExtendsConfigTrait;

		/**
		 * Key=>Class dictionary of condition types
		 *
		 * @var array<string, string>
		 */
		protected static $condition_types = [
			'url'           => UrlCondition::class,
			'custom'        => CustomCondition::class,
			'multiple'      => MultipleCondition::class,
			'negate'        => NegateCondition::class,
			'post_id'       => PostIdCondition::class,
			'post_slug'     => PostSlugCondition::class,
			'post_status'   => PostStatusCondition::class,
			'post_template' => PostTemplateCondition::class,
			'post_type'     => PostTypeCondition::class,
			'query_var'     => QueryVarCondition::class,
			'ajax'          => AjaxCondition::class,
			'admin'         => AdminCondition::class,
			'model'         => ModelCondition::class,
		];


		public function register( $container ) {


			$this->extendConfig( $container, 'routes', [
				'web'   => [
					'definitions' => '',
					'attributes'  => [
						'middleware' => [ 'web' ],
						'namespace'  => 'App\\Controllers\\Web\\',
						'handler'    => 'WPEmerge\\Controllers\\WordPressController@handle',
					],
				],
				'admin' => [
					'definitions' => '',
					'attributes'  => [
						'middleware' => [ 'admin' ],
						'namespace'  => 'App\\Controllers\\Admin\\',
					],
				],
				'ajax'  => [
					'definitions' => '',
					'attributes'  => [
						'middleware' => [ 'ajax' ],
						'namespace'  => 'App\\Controllers\\Ajax\\',
					],
				],
			] );

			$container[ WPEMERGE_ROUTING_CONDITION_TYPES_KEY ] = static::$condition_types;

			$container->singleton( WPEMERGE_ROUTING_ROUTER_KEY, function ( $c ) {

				return new Router(
					$c[ WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY ],
					$c[ WPEMERGE_HELPERS_HANDLER_FACTORY_KEY ]
				);
			} );

			$container->singleton( WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY, function ( $c ) {

				return new ConditionFactory( $c[ WPEMERGE_ROUTING_CONDITION_TYPES_KEY ], $c[ WPEMERGE_CONTAINER_ADAPTER ] );

			} );

			$container->bind( WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY, function ( $c ) {

				return new RouteBlueprint( $c[ WPEMERGE_ROUTING_ROUTER_KEY ], $c[ WPEMERGE_VIEW_SERVICE_KEY ] );

			} );

			$app = $container[ WPEMERGE_APPLICATION_KEY ];
			$app->alias( 'route', WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY );
			$app->alias( 'routeUrl', WPEMERGE_ROUTING_ROUTER_KEY, 'getRouteUrl' );

		}


		public function bootstrap( $container ) {

			// Nothing to bootstrap.

		}

	}
