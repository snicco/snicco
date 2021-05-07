<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\RouteMatcher;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
	use WPEmerge\Routing\Conditions\AdminCondition;
	use WPEmerge\Routing\Conditions\AjaxCondition;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Routing\Conditions\CustomCondition;
	use WPEmerge\Routing\Conditions\NegateCondition;
	use WPEmerge\Routing\Conditions\PostIdCondition;
	use WPEmerge\Routing\Conditions\PostSlugCondition;
	use WPEmerge\Routing\Conditions\PostStatusCondition;
	use WPEmerge\Routing\Conditions\PostTemplateCondition;
	use WPEmerge\Routing\Conditions\PostTypeCondition;
	use WPEmerge\Routing\Conditions\QueryVarCondition;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;
	use WPEmerge\Routing\RouteRegistrar;
	use WPEmerge\Support\Arr;
	use WPEmerge\Traits\ExtendsConfig;

	class RoutingServiceProvider implements ServiceProviderInterface {

		use ExtendsConfig;

		/**
		 * Key=>Class dictionary of condition types
		 *
		 * @var array<string, string>
		 */
		protected static $condition_types = [
			'custom'        => CustomCondition::class,
			'negate'        => NegateCondition::class,
			'post_id'       => PostIdCondition::class,
			'post_slug'     => PostSlugCondition::class,
			'post_status'   => PostStatusCondition::class,
			'post_template' => PostTemplateCondition::class,
			'post_type'     => PostTypeCondition::class,
			'query_var'     => QueryVarCondition::class,
			'ajax'          => AjaxCondition::class,
			'admin'         => AdminCondition::class,
		];


		public function register( $container ) {



			$container->instance(WPEMERGE_ROUTING_CONDITION_TYPES_KEY, static::$condition_types);

			$container->singleton(RouteMatcher::class, function ($c) {

				$config = $c[WPEMERGE_CONFIG_KEY];

				if ( ! isset( $config['routes']['cache']['enable']) ) {

					return new FastRouteMatcher();

				}

				$cache_file = Arr::get($config['routes']['cache'], 'enabled', 'null' );

				if ( ! file_exists($cache_file ) ) {

					throw new ConfigurationException(
						'Invalid file provided for route caching'
					);

				}

				/** @todo Named routes will not work right now with caching enabled.  */
				/** @todo Need a way to also cache routes outside of the route matcher */
				return new CachedFastRouteMatcher( new FastRouteMatcher(), $cache_file );


			} );

			$container->singleton( WPEMERGE_ROUTING_ROUTER_KEY, function ( $c ) {

				return new Router(
					$c[WPEMERGE_CONTAINER_ADAPTER],
					new RouteCollection(
						$c[WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY],
						$c[HandlerFactory::class],
						$c[RouteMatcher::class]
					)


				);
			} );

			$container->singleton( WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY, function ( $c ) {

				return new ConditionFactory( $c[ WPEMERGE_ROUTING_CONDITION_TYPES_KEY ], $c[ WPEMERGE_CONTAINER_ADAPTER ] );

			} );




		}


		public function bootstrap( $container ) {

			$router = $container->make(WPEMERGE_ROUTING_ROUTER_KEY );
			$config = $container->make(WPEMERGE_CONFIG_KEY);

			( new RouteRegistrar($router, $config ) )->loadRoutes();

		}

	}
