<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\Contracts\RequestInterface as Request;

	class RouteSegmentsTest extends TestCase {

		use SetUpRouter;

		/**
		 *
		 *
		 *
		 *
		 *
		 * ROUTE PARAMETERS, NATIVE FAST ROUTE SYNTAX
		 *
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function route_parameters_are_captured() {

			$this->router->post( '/user/{id}/{name}' )
			             ->handle( function ( Request $request, $id, $name = 'admin' ) {

				             return $name . $id;

			             } );

			$response = $this->router->runRoute( $this->request( 'post', '/user/12/calvin' ) );
			$this->seeResponse( 'calvin12', $response );


		}

		/** @test */
		public function custom_regex_can_be_defined_for_route_parameters() {

			$routes = function () {

				$this->router->post( '/user/{id:\d+}/{name:calvin|john}' )
				             ->handle( function ( Request $request, $id, $name = 'admin' ) {

					             return $name . $id;

				             } );

			};

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/12/calvin' ) );
			$this->seeResponse( 'calvin12', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/12/john' ) );
			$this->seeResponse( 'john12', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/a/calvin' ) );
			$this->seeResponse( null, $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/12/jane' ) );
			$this->seeResponse( null, $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/12' ) );
			$this->seeResponse( null, $response );

		}

		/** @test */
		public function optional_parameters_work_at_the_end_of_a_route() {

			$routes = function () {

				$this->router->post( '/user/{id:\d+}[/{name}]' )
				             ->handle( function ( Request $request, $id, $name = 'admin' ) {

					             return $name . $id;

				             } );
			};

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/12/calvin' ) );
			$this->seeResponse( 'calvin12', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/12' ) );
			$this->seeResponse( 'admin12', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/ab' ) );
			$this->seeResponse( null, $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/ab/calvin' ) );
			$this->seeResponse( null, $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/user/calvin/12' ) );
			$this->seeResponse( null, $response );


		}

		/** @test */
		public function every_segment_after_an_optional_part_will_be_its_own_capture_group_but_not_required() {

			$routes = function () {

				$this->router->post( '/team/{id:\d+}[/{name}[/{player}]]' )
				             ->handle( function ( Request $request, $id, $name = 'foo_team', $player = 'foo_player' ) {

					             return $name . ':' . $id . ':' . $player;

				             } );

			};

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/dortmund/calvin' ) );
			$this->seeResponse( 'dortmund:1:calvin', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/dortmund' ) );
			$this->seeResponse( 'dortmund:1:foo_player', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/12' ) );
			$this->seeResponse( 'foo_team:12:foo_player', $response );

		}

		/** @test */
		public function optional_parameters_work_with_custom_regex() {

			$routes = function () {


				$this->router->get( 'users/{id}[/{name:[a-z]+}]', function ( Request $request, $id, $name = 'admin' ) {

					return $name . $id;

				} );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1/calvin' );
			$this->seeResponse( 'calvin1', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', 'users/1' );
			$this->seeResponse( 'admin1', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', 'users/1/12' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );


		}


		/**
		 *
		 *
		 *
		 *
		 *
		 * ROUTE PARAMETERS, CUSTOM API
		 *
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function regex_can_be_added_as_a_condition_without_needing_array_syntax() {

			$routes = function () {

				$this->router->get( 'users/{user}', function () {

					return 'foo';

				} )->and( 'user', '[0-9]+' );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calvin' );
			$this->assertNull( $this->router->runRoute( $request ) );


		}

		/** @test */
		public function regex_can_be_added_as_a_condition_as_array_syntax() {

			$routes = function () {

				$this->router->get( 'users/{user}', function () {

					return 'foo';

				} )->and( [ 'user', '[0-9]+' ] );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calvin' );
			$this->assertNull( $this->router->runRoute( $request ) );


		}

		/** @test */
		public function multiple_regex_conditions_can_be_added_to_an_url_condition() {

			$routes = function () {

				$this->router->get( '/user/{id}/{name}', function ( Request $request, $id, $name ) {

					return $name . $id;

				} )
				             ->and( [ 'id' => '[0-9]+', 'name' => '[a-z]+' ] );


			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/user/1/calvin' );
			$this->seeResponse( 'calvin1', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1/1' );
			$this->assertNull( $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calvin/calvin' );
			$this->assertNull( $this->router->runRoute( $request ) );

		}

		/** @test */
		public function optional_parameters_work_at_the_end_of_the_url() {

			$routes = function () {

				$this->router->get( 'users/{id}/{name?}', function ( Request $request, $id, $name = 'admin' ) {

					return $name . $id;

				} );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1/calvin' );
			$this->seeResponse( 'calvin1', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', 'users/1' );
			$this->seeResponse( 'admin1', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function multiple_parameters_can_optional() {

			$routes = function () {

				// Preceding Group is capturing
				$this->router->post( '/team/{id:\d+}/{name?}/{player?}' )
				             ->handle( function ( Request $request, $id, $name = 'foo_team', $player = 'foo_player' ) {

					             return $name . ':' . $id . ':' . $player;

				             } );

			};

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/dortmund/calvin' ) );
			$this->seeResponse( 'dortmund:1:calvin', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/dortmund' ) );
			$this->seeResponse( 'dortmund:1:foo_player', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/12' ) );
			$this->seeResponse( 'foo_team:12:foo_player', $response );

			$routes = function () {

				// Preceding group is required but not capturing
				$this->router->post( '/users/{name?}/{gender?}/{age?}' )
				             ->handle( function ( Request $request, $name = 'john', $gender = 'm', $age = '21' ) {


					             return $name . ':' . $gender . ':' . $age;

				             } );

			};

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/users/calvin/male/23' ) );
			$this->seeResponse( 'calvin:male:23', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/users/calvin/male' ) );
			$this->seeResponse( 'calvin:male:21', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/users/calvin/' ) );
			$this->seeResponse( 'calvin:m:21', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/users/' ) );
			$this->seeResponse( 'john:m:21', $response );


		}

		/** @test */
		public function optional_parameters_work_with_our_custom_api() {

			$routes = function () {

				$this->router->get( 'users/{id}/{name?}', function ( Request $request, $id, $name = 'admin' ) {

					return $name . $id;

				} )->and( 'name', '[a-z]+' );


			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1/calvin' );
			$this->seeResponse( 'calvin1', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', 'users/1' );
			$this->seeResponse( 'admin1', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', 'users/1/12' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );


		}

		/** @test */
		public function multiple_parameters_can_be_optional_and_have_custom_regex() {

			$routes = function () {

				// Preceding Group is capturing
				$this->router->post( '/team/{id}/{name?}/{age?}' )
				             ->and( [ 'name' => '[a-z]+', 'age' => '\d+' ] )
				             ->handle( function ( Request $request, $id, $name = 'foo_team', $age = 21 ) {

					             return $name . ':' . $id . ':' . $age;

				             } );

			};

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/dortmund/23' ) );
			$this->seeResponse( 'dortmund:1:23', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/dortmund' ) );
			$this->seeResponse( 'dortmund:1:21', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/12' ) );
			$this->seeResponse( 'foo_team:12:21', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/dortmund/fail' ) );
			$this->seeResponse( null, $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'post', '/team/1/123/123' ) );
			$this->seeResponse( null, $response );


		}

		/** @test */
		public function adding_regex_can_be_done_as_a_fluent_api() {

			$routes = function () {

				$this->router->get( 'users/{user_id}/{name}', function () {

					return 'foo';

				} )->and( 'user_id', '[0-9]+' )->and( 'name', 'calvin' );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1/calvin' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1/john' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/w/calvin' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );

		}

		/** @test */
		public function only_alpha_can_be_added_to_a_segment_as_a_helper_method() {


			$routes = function () {

				$this->router->get( 'users/{name}', function () {

					return 'foo';

				} )->andAlpha( 'name' );

				$this->router->get( 'teams/{name}/{player}', function () {

					return 'foo';

				} )->andAlpha( 'name', 'player' );

				$this->router->get( 'countries/{country}/{city}', function () {

					return 'foo';

				} )->andAlpha( [ 'country', 'city' ] );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calvin' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/cal1vin' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/teams/dortmund/calvin' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/teams/1/calvin' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/teams/dortmund/1' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/countries/germany/berlin' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/countries/germany/1' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/countries/1/berlin' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );


		}

		/** @test */
		public function only_alphanumerical_can_be_added_to_a_segment_as_a_helper_method() {


			$routes = function () {

				$this->router->get( 'users/{name}', function () {

					return 'foo';

				} )->andAlphaNumerical( 'name' );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calvin' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calv1in' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function only_number_can_be_added_to_a_segment_as_a_helper_method() {


			$routes = function () {

				$this->router->get( 'users/{name}', function () {

					return 'foo';

				} )->andNumber( 'name' );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/1' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calvin' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/users/calv1in' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );


		}

		/** @test */
		public function only_one_of_can_be_added_to_a_segment_as_a_helper_method() {


			$routes = function () {

				$this->router->get( 'home/{locale}', function ( Request $request, $locale ) {

					return $locale;

				} )->andEither( 'locale', [ 'en', 'de' ] );

			};

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/home/en' );
			$this->seeResponse( 'en', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/home/de' );
			$this->seeResponse( 'de', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->request( 'GET', '/home/es' );
			$this->seeResponse( null, $this->router->runRoute( $request ) );


		}


	}