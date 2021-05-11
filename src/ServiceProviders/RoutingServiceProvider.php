<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use Codeception\Step\Condition;
	use WPEmerge\Contracts\RouteMatcher;
	use WPEmerge\Contracts\ServiceProvider;
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

	class RoutingServiceProvider extends ServiceProvider {


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


		public function register() :void  {


			$this->container->instance('route.conditions', static::$condition_types);

			$this->container->singleton(RouteMatcher::class, function () {


				if ( ! isset( $this->config['routes']['cache']['enable']) ) {

					return new FastRouteMatcher();

				}

				$cache_file = Arr::get($this->config['routes']['cache'], 'enabled', 'null' );

				if ( ! file_exists( $cache_file ) ) {

					throw new ConfigurationException(
						'Invalid file provided for route caching'
					);

				}

				/** @todo Named routes will not work right now with caching enabled.  */
				/** @todo Need a way to also cache routes outside of the route matcher */
				return new CachedFastRouteMatcher( new FastRouteMatcher(), $cache_file );


			} );

			$this->container->singleton( Router::class, function () {

				return new Router(
					$this->container,
					new RouteCollection(
						$this->container->make(ConditionFactory::class),
						$this->container->make(HandlerFactory::class),
						$this->container->make(RouteMatcher::class)
					)


				);
			} );

			$this->container->singleton( ConditionFactory::class, function () {

				return new ConditionFactory(

					$this->container->make('route.conditions'),
					$this->container

				);

			} );




		}


		public function bootstrap() :void {

			$router = $this->container->make(Router::class );

			( new RouteRegistrar($router, $this->config ) )->loadRoutes();

		}

	}
