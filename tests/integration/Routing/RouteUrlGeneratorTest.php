<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use Codeception\TestCase\WPTestCase;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Routing\Conditions\AdminCondition;
	use WPEmerge\Routing\Conditions\NegateCondition;
	use WPEmerge\Support\Str;

	class RouteUrlGeneratorTest extends WPTestCase {

		use SetUpRouter;

		/**
		 *
		 *
		 *
		 *
		 * NAMED ROUTES
		 *
		 *
		 *
		 *
		 *
		 */

		private function conditions() : array {

			return [

				'admin'  => AdminCondition::class,
				'negate' => NegateCondition::class,
			];

		}

		/** @test */
		public function a_route_can_be_named() {

			$this->router->get( 'foo' )->name( 'foo_route' );
			$url = $this->router->getRouteUrl( 'foo_route' );
			$this->seeUrl( '/foo/', $url );

			$this->router->name( 'bar_route' )->get( 'bar' );
			$url = $this->router->getRouteUrl( 'bar_route' );
			$this->seeUrl( '/bar/', $url );

		}

		/** @test */
		public function route_names_are_merged_on_multiple_levels() {

			$this->router
				->name( 'foo' )
				->group( function () {

					$this->router->name( 'bar' )->group( function () {

						$this->router->get( 'baz' )->name( 'baz' );

					} );

					$this->router->get( 'biz' )->name( 'biz' );


				} );

			$this->seeUrl( '/baz/', $this->router->getRouteUrl( 'foo.bar.baz' ) );
			$this->seeUrl( '/biz/', $this->router->getRouteUrl( 'foo.biz' ) );

			$this->expectExceptionMessage( 'no named route' );

			$this->seeUrl( '/baz/', $this->router->getRouteUrl( 'foo.bar.biz' ) );


		}

		/** @test */
		public function group_names_get_applied_to_child_routes() {

			$this->router
				->name( 'foo' )
				->group( function () {

					$this->router->get( 'bar' )->name( 'bar' );

					$this->router->get( 'baz' )->name( 'baz' );

					$this->router->name( 'biz' )->get( 'biz' );

				} );

			$this->seeUrl( '/bar/', $this->router->getRouteUrl( 'foo.bar' ) );
			$this->seeUrl( '/baz/', $this->router->getRouteUrl( 'foo.baz' ) );
			$this->seeUrl( '/biz/', $this->router->getRouteUrl( 'foo.biz' ) );


		}

		/** @test */
		public function urls_for_routes_with_required_segments_can_be_generated() {

			$this->router->get( '/foo/{required}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', [ 'required' => 'bar' ] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function urls_for_routes_with_optional_segments_can_be_generated() {

			$this->router->get( 'foo/{required}/{optional?}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', [
				'required' => 'bar',
				'optional' => 'baz',
			] );
			$this->seeUrl( '/foo/bar/baz/', $url );

		}

		/** @test */
		public function optional_segments_can_be_left_blank() {

			$this->router->get( 'foo/{optional?}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo' );
			$this->seeUrl( '/foo/', $url );

			$this->router->get( 'bar/{required}/{optional?}' )->name( 'bar' );
			$url = $this->router->getRouteUrl( 'bar', [ 'required' => 'baz' ] );
			$this->seeUrl( '/bar/baz/', $url );


		}

		/** @test */
		public function optional_segments_can_be_created_after_fixed_segments() {

			$this->router->get( 'foo/{optional?}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', [ 'optional' => 'bar' ] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function multiple_optional_segments_can_be_created() {

			$this->router->get( 'foo/{opt1?}/{opt2?}/' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', [ 'opt1' => 'bar', 'opt2' => 'baz' ] );
			$this->seeUrl( '/foo/bar/baz/', $url );

			$this->router->get( 'bar/{required}/{opt1?}/{opt2?}/' )->name( 'bar' );
			$url = $this->router->getRouteUrl( 'bar', [
				'required' => 'biz',
				'opt1'     => 'bar',
				'opt2'     => 'baz',
			] );
			$this->seeUrl( '/bar/biz/bar/baz/', $url );


		}

		/** @test */
		public function required_segments_can_be_created_with_regex_constraints() {

			$this->router->get( '/foo/{required}' )->name( 'foo' )->and( 'required', '\w+' );
			$url = $this->router->getRouteUrl( 'foo', [ 'required' => 'bar' ] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function optional_segments_can_be_created_with_regex() {

			$this->router->get( '/foo/{optional?}' )->name( 'foo' )->and( 'optional', '\w+' );
			$url = $this->router->getRouteUrl( 'foo', [ 'optional' => 'bar' ] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function required_and_optional_segments_can_be_created_with_regex() {

			$this->router->get( '/foo/{required}/{optional?}' )
			             ->name( 'foo' )
			             ->and( [ 'required', '\w+', 'optional', '\w+' ] );

			$url = $this->router->getRouteUrl( 'foo', [ 'required' => 'bar' ] );
			$this->seeUrl( '/foo/bar/', $url );

			$this->router->get( '/bar/{required}/{optional?}' )
			             ->name( 'bar' )
			             ->and( [ 'required' => '\w+', 'optional' => '\w+' ] );

			$url = $this->router->getRouteUrl( 'bar', [
				'required' => 'baz',
				'optional' => 'biz',
			] );
			$this->seeUrl( '/bar/baz/biz/', $url );

			$this->router->get( '/foo/{required}/{optional1?}/{optional2?}' )
			             ->name( 'foobar' )
			             ->and( [
				             'required'  => '\w+',
				             'optional1' => '\w+',
				             'optional2' => '\w+',
			             ] );

			$url = $this->router->getRouteUrl( 'foobar', [
				'required'  => 'bar',
				'optional1' => 'baz',
				'optional2' => 'biz',
			] );
			$this->seeUrl( '/foo/bar/baz/biz/', $url );


		}

		/** @test */
		public function missing_required_arguments_throw_an_exception() {

			$this->expectExceptionMessage( 'Required route segment: {required} missing' );

			$this->router->get( 'foo/{required}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo' );


		}

		/** @test */
		public function an_exception_gets_thrown_if_the_passed_arguments_dont_satisfy_regex_constraints() {

			$this->expectExceptionMessage(
				'The provided value [#] is not valid for the route: [foo]' );

			$this->router->get( '/foo/{required}' )
			             ->name( 'foo' )
			             ->and( [ 'required' => '\w+' ] );

			$this->router->getRouteUrl( 'foo', [ 'required' => '#' ] );

		}

		/** @test */
		public function custom_conditions_that_can_be_transformed_take_precedence_over_http_conditions() {


			$this->router->get( 'foo' )->name( 'foo_route' )->where( ConditionWithUrl::class );
			$url = $this->router->getRouteUrl( 'foo_route' );
			$this->seeUrl( '/foo/bar', $url );

		}


		/**
		 *
		 *
		 *
		 *
		 * EDGE CASES
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function the_route_contains_segments_that_have_regex_using_curly_brackets_resulting_in_triple_curly_brackets_at_the_end_of_the_url() {

			$this->router
				->get( '/foo/{bar}' )
				->name( 'foo' )
				->and( 'bar', 'a{2,}' );

			$url = $this->router->getRouteUrl( 'foo', [ 'bar' => 'aaa' ] );
			$this->seeUrl( '/foo/aaa/', $url );

			$url = $this->router->getRouteUrl( 'foo', [ 'bar' => 'aaaa' ] );
			$this->seeUrl( '/foo/aaaa/', $url );

			try {

				$this->router->getRouteUrl( 'foo', [ 'bar' => 'a' ] );
				$this->fail( 'Invalid constraint created a route.' );

			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString(
					'The provided value [a] is not valid for the route',
					$e->getMessage()
				);

			}

			try {

				$this->router->getRouteUrl( 'foo', [ 'bar' => 'bbbb' ] );
				$this->fail( 'Invalid constraint created a route.' );
			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString(
					'The provided value [bbbb] is not valid for the route',
					$e->getMessage()
				);

			}


		}

		/** @test */
		public function the_route_contains_segments_that_have_regex_using_curly_brackets_and_square_brackets() {

			$this->router
				->get( '/foo/{bar}' )
				->name( 'foo' )
				->and( 'bar', 'a{2,}[calvin]' );

			$url = $this->router->getRouteUrl( 'foo', [ 'bar' => 'aacalvin' ] );
			$this->seeUrl( '/foo/aacalvin/', $url );

			$this->expectExceptionMessage( 'The provided value [aajohn] is not valid for the route' );

			$this->router->getRouteUrl( 'foo', [ 'bar' => 'aajohn' ] );

		}

		/** @test */
		public function problematic_regex_inside_required_and_optional_segments() {

			$this->router
				->get( '/teams/{team}/{player?}' )
				->name( 'teams' )
				->and( [

					'team'   => 'm{1}.+united[xy]',
					'player' => 'a{2,}[calvin]',

				] );

			$url = $this->router->getRouteUrl( 'teams', [
				'team'   => 'manchesterunitedx',
				'player' => 'aacalvin',
			] );
			$this->seeUrl( '/teams/manchesterunitedx/aacalvin/', $url );

			// Fails because not starting with m.
			try {

				$this->router->getRouteUrl( 'teams', [
					'team'   => 'lanchesterunited',
					'player' => 'aacalvin',
				] );
				$this->fail( 'Invalid constraint created a route.' );

			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString(
					'The provided value [lanchesterunited] is not valid for the route',
					$e->getMessage()
				);

			}

			// Fails because not using united.
			try {

				$this->router->getRouteUrl( 'teams', [
					'team'   => 'manchestercityx',
					'player' => 'aacalvin',
				] );
				$this->fail( 'Invalid constraint created a route.' );

			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString(
					'The provided value [manchestercityx] is not valid for the route',
					$e->getMessage()
				);

			}

			// Fails because not using x or y at the end.
			try {

				$this->router->getRouteUrl( 'teams', [
					'team'   => 'manchesterunitedz',
					'player' => 'aacalvin',
				] );
				$this->fail( 'Invalid constraint created a route.' );

			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString(
					'The provided value [manchesterunitedz] is not valid for the route',
					$e->getMessage()
				);

			}

			// Fails because optional parameter is present but doesnt match regex, only one a
			try {

				$this->router->getRouteUrl( 'teams', [
					'team'   => 'manchesterunitedx',
					'player' => 'acalvin',
				] );
				$this->fail( 'Invalid constraint created a route.' );

			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString(
					'The provided value [acalvin] is not valid for the route',
					$e->getMessage()
				);

			}

		}


		private function seeUrl( $route_path, $result ) {

			$expected = rtrim( SITE_URL, '/' ) . '/' . ltrim( $route_path, '/' );

			// Strip https, http
			$expected = Str::after( $expected, '://' );
			$result   = Str::after( $result, '://' );

			$this->assertSame( $expected, $result );

		}


	}


	class ConditionWithUrl implements UrlableInterface, ConditionInterface {


		public function toUrl( $arguments = [] ) {

			return SITE_URL . 'foo/bar';

		}

		public function isSatisfied( RequestInterface $request ) {

			return true;

		}

		public function getArguments( RequestInterface $request ) {

			return [];

		}

	}