<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Routing;

	use Mockery;
    use Tests\UnitTest;
    use Tests\traits\SetUpRouter;
    use Tests\stubs\Bar;
	use Tests\stubs\Controllers\Web\ControllerWithDependencies;
	use Tests\stubs\Controllers\Web\TeamsController;
	use Tests\stubs\Foo;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;

	class RouteActionDependencyInjectionTest extends UnitTest {

		use SetUpRouter;

        protected function beforeTestRun()
        {

            $this->newRouter($c = $this->createContainer());
            WP::setFacadeContainer($c);

        }

        protected function beforeTearDown()
        {
            WP::setFacadeContainer(null);
            WP::clearResolvedInstances();
            Mockery::close();
        }


		/** @test */
		public function dependencies_for_controller_actions_are_resolved() {

			$this->router->get( '/foo', ControllerWithDependencies::class . '@handle');

			$request = $this->request( 'GET', '/foo' );
			$this->assertOutput( 'foo_controller', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function method_dependencies_for_controller_actions_are_resolved () {

			$this->router->get( '/foo', ControllerWithDependencies::class . '@withMethodDependency');

			$request = $this->request( 'GET', '/foo' );
			$this->assertOutput( 'foobar_controller', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function route_segment_values_are_passed_to_the_controller_method () {

			$this->router->get('teams/{team}/{player}', TeamsController::class . '@handle');

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->assertOutput(  'dortmund:calvin', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function additional_dependencies_are_passed_to_the_controller_method_after_route_segments () {

			$this->router->get('teams/{team}/{player}', TeamsController::class . '@withDependencies');

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->assertOutput(  'dortmund:calvin:foo:bar', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function arguments_from_conditions_are_passed_after_route_segments_and_before_dependencies () {

			$this->router
				->get('teams/{team}/{player}', TeamsController::class . '@withConditions')
				->where(function ($baz, $biz ) {

					return $baz === 'baz' && $biz === 'biz';

				}, 'baz', 'biz');

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->assertOutput(  'dortmund:calvin:baz:biz:foo:bar', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function closure_actions_also_get_all_dependencies_injected_in_the_correct_order(  ) {

			$this->router
				->get('teams/{team}/{player}')
				->where(function ($baz, $biz ) {

					return $baz === 'baz' && $biz === 'biz';

				}, 'baz', 'biz')
				->handle( function ( Request $request, $team, $player, $baz, $biz ,Foo $foo, Bar $bar) {

					return $team . ':' . $player . ':' .  $baz . ':' . $biz  . ':' . $foo->foo . ':' . $bar->bar;


				});

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->assertOutput(  'dortmund:calvin:baz:biz:foo:bar', $this->router->runRoute( $request ) );

		}


	}