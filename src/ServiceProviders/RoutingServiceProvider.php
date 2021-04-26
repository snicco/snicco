<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Handlers\HandlerFactory;
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
	use WPEmerge\Routing\RouteRegistrar;


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


			// $this->extendConfig( $container, 'routes', [
			// 	'web'   => [
			// 		'definitions' => '',
			// 		'attributes'  => [
			// 			'middleware' => [ 'web' ],
			// 			'namespace'  => 'App\\Controllers\\Web\\',
			// 			'handler'    => 'WPEmerge\\Controllers\\WordPressController@handle',
			// 		],
			// 	],
			// 	'admin' => [
			// 		'definitions' => '',
			// 		'attributes'  => [
			// 			'middleware' => [ 'admin' ],
			// 			'namespace'  => 'App\\Controllers\\Admin\\',
			// 		],
			// 	],
			// 	'ajax'  => [
			// 		'definitions' => '',
			// 		'attributes'  => [
			// 			'middleware' => [ 'ajax' ],
			// 			'namespace'  => 'App\\Controllers\\Ajax\\',
			// 		],
			// 	],
			// ] );

			$container->instance(WPEMERGE_ROUTING_CONDITION_TYPES_KEY, static::$condition_types);


			$container->singleton( WPEMERGE_ROUTING_ROUTER_KEY, function ( $c ) {

				return new Router(
					$c[WPEMERGE_CONTAINER_ADAPTER],
					$c[ WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY ],
					$c[HandlerFactory::class]

				);
			} );

			$container->singleton( WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY, function ( $c ) {

				return new ConditionFactory( $c[ WPEMERGE_ROUTING_CONDITION_TYPES_KEY ], $c[ WPEMERGE_CONTAINER_ADAPTER ] );

			} );

			$container->bind( WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY, function ( $c ) {

				return new RouteBlueprint( $c[ WPEMERGE_ROUTING_ROUTER_KEY ], $c[ WPEMERGE_VIEW_SERVICE_KEY ] );

			} );



		}


		public function bootstrap( $container ) {

			$blueprint = $container->make(WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY );
			$config = $container->make(WPEMERGE_CONFIG_KEY);

			( new RouteRegistrar($blueprint, $config ) )->loadRoutes();

		}

	}
