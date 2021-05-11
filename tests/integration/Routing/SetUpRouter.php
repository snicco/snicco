<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use SniccoAdapter\BaseContainerAdapter;
	use Tests\TestRequest;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Http\Response;
	use WPEmerge\Routing\Conditions\AdminCondition;
	use WPEmerge\Routing\Conditions\AjaxCondition;
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

	trait SetUpRouter {

		/**
		 * @var \WPEmerge\Routing\Router
		 */
		private $router;

		/** @var \Contracts\ContainerAdapter */
		private $container;

		protected function setUp() : void {

			parent::setUp();

			$this->newRouter();

			$GLOBALS['test'] = [];

		}

		private function newRouterWith( \Closure $routes ) {

			$this->newRouter();

			$routes( $this->router );

		}

		private function newRouter() {

			$conditions = is_callable([$this, 'conditions']) ? $this->conditions() : $this->allConditions();

			$container         = new BaseContainerAdapter();
			$condition_factory = new ConditionFactory( $conditions, $container );
			$handler_factory   = new HandlerFactory( [], $container );
			$route_collection  = new RouteCollection(
				$condition_factory,
				$handler_factory,
				new FastRouteMatcher()
			);
			$this->container = $container;
			$this->router      = new Router( $container, $route_collection );

		}

		protected function tearDown() : void {


			parent::tearDown();

			unset( $GLOBALS['test'] );
		}

		private function request( $method, $path ) : TestRequest {

			return TestRequest::from( $method, $path );

		}

		private function seeResponse( $expected, $response ) {

			if ( $response instanceof Response ) {

				$response = $response->body();

			}

			$this->assertSame( $expected, $response );

		}

		private function allConditions() : array {

			return [

				'url'                  => UrlCondition::class,
				'custom'               => CustomCondition::class,
				'negate'               => NegateCondition::class,
				'post_id'              => PostIdCondition::class,
				'post_slug'            => PostSlugCondition::class,
				'post_status'          => PostStatusCondition::class,
				'post_template'        => PostTemplateCondition::class,
				'post_type'            => PostTypeCondition::class,
				'query_var'            => QueryVarCondition::class,
				'ajax'                 => AjaxCondition::class,
				'admin'                => AdminCondition::class,
				'true'                 => \Tests\stubs\Conditions\TrueCondition::class,
				'false'                => \Tests\stubs\Conditions\FalseCondition::class,
				'maybe'                => \Tests\stubs\Conditions\MaybeCondition::class,
				'unique'               => \Tests\stubs\Conditions\UniqueCondition::class,
				'dependency_condition' => \Tests\stubs\Conditions\ConditionWithDependency::class,

			];

		}

		private function assertMiddlewareRunTimes(int $times, $class) {

			$this->assertSame(
				$times, $GLOBALS['test'][ $class::run_times ],
				'Middleware [' .$class . '] was supposed to run: ' . $times . ' times. Actual: ' .$GLOBALS['test'][ $class::run_times ]
			);

		}

		private function assertRouteActionConstructedTimes(int $times, $class) {

			$actual = $GLOBALS['test'][ $class::constructed_times ] ?? 0;

			$this->assertSame(
				$times,  $actual ,
				'RouteAction [' .$class . '] was supposed to run: ' . $times . ' times. Actual: ' . $GLOBALS['test'][ $class::constructed_times ]
			);

		}

	}