<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\stubs\Bar;
	use Tests\stubs\Controllers\Web\ControllerWithDependencies;
	use Tests\stubs\Controllers\Web\TeamsController;
	use Tests\stubs\Foo;
	use Tests\TestRequest;
	use WPEmerge\Http\Response;

	class RouteActionDependencyInjectionTest extends TestCase {

		use SetUpRouter;

		/** @test */
		public function dependencies_for_controller_actions_are_resolved() {

			$this->router->get( '/foo', ControllerWithDependencies::class . '@handle');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foo_controller', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function method_dependencies_for_controller_actions_are_resolved () {

			$this->router->get( '/foo', ControllerWithDependencies::class . '@withMethodDependency');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobar_controller', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function route_segment_values_are_passed_to_the_controller_method () {

			$this->router->get('teams/{team}/{player}', TeamsController::class . '@handle');

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->seeResponse(  'dortmund:calvin', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function additional_dependencies_are_passed_to_the_controller_method_after_route_segments () {

			$this->router->get('teams/{team}/{player}', TeamsController::class . '@withDependencies');

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->seeResponse(  'dortmund:calvin:foo:bar', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function arguments_from_conditions_are_passed_after_route_segments_and_before_dependencies () {

			$this->router
				->get('teams/{team}/{player}', TeamsController::class . '@withConditions')
				->where(function ($baz, $biz ) {

					return $baz === 'baz' && $biz === 'biz';

				}, 'baz', 'biz');

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->seeResponse(  'dortmund:calvin:baz:biz:foo:bar', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function closure_actions_also_get_all_dependencies_injected_in_the_correct_order(  ) {

			$this->router
				->get('teams/{team}/{player}')
				->where(function ($baz, $biz ) {

					return $baz === 'baz' && $biz === 'biz';

				}, 'baz', 'biz')
				->handle( function ( TestRequest $request, $team, $player, $baz, $biz ,Foo $foo, Bar $bar) {

					$request->body = $team . ':' . $player . ':' .  $baz . ':' . $biz  . ':' . $foo->foo . ':' . $bar->bar;

					return new Response( $request->body );

				});

			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->seeResponse(  'dortmund:calvin:baz:biz:foo:bar', $this->router->runRoute( $request ) );

		}


	}