<?php


	namespace Tests\integration\Routing;

	use Codeception\TestCase\WPTestCase;
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

		private function conditions () : array {

			return [

				'admin' => AdminCondition::class,
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
		public function urls_for_routes_with_required_segments_can_be_generated () {

			$this->router->get( '/foo/{required}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', ['required' => 'bar'] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function urls_for_routes_with_optional_segments_can_be_generated() {

			$this->router->get( 'foo/{required}/{optional?}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', ['required' => 'bar', 'optional' => 'baz'] );
			$this->seeUrl( '/foo/bar/baz/', $url );

		}

		/** @test */
		public function optional_segments_can_be_left_blank () {

			$this->router->get( 'foo/{optional?}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo' );
			$this->seeUrl( '/foo/', $url );

			$this->router->get( 'bar/{required}/{optional?}' )->name( 'bar' );
			$url = $this->router->getRouteUrl( 'bar', ['required' => 'baz']);
			$this->seeUrl( '/bar/baz/', $url );



		}

		/** @test */
		public function optional_segments_can_be_created_after_fixed_segments () {

			$this->router->get( 'foo/{optional?}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', ['optional' => 'bar'] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function multiple_optional_segments_can_be_created () {

			$this->router->get( 'foo/{opt1?}/{opt2?}/' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo', ['opt1' => 'bar', 'opt2' => 'baz']);
			$this->seeUrl( '/foo/bar/baz/', $url );

			$this->router->get( 'bar/{required}/{opt1?}/{opt2?}/' )->name( 'bar' );
			$url = $this->router->getRouteUrl( 'bar', ['required' => 'biz','opt1' => 'bar', 'opt2' => 'baz']);
			$this->seeUrl( '/bar/biz/bar/baz/', $url );



		}

		/** @test */
		public function required_segments_can_be_created_with_regex_constraints () {

			$this->router->get( '/foo/{required}' )->name( 'foo' )->and('required', '\w+');
			$url = $this->router->getRouteUrl( 'foo', ['required' => 'bar'] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function optional_segments_can_be_created_with_regex() {

			$this->router->get( '/foo/{optional?}' )->name( 'foo' )->and('optional', '\w+');
			$url = $this->router->getRouteUrl( 'foo', ['optional' => 'bar'] );
			$this->seeUrl( '/foo/bar/', $url );

		}

		/** @test */
		public function required_and_optional_segments_can_be_created_with_regex () {

			$this->router->get( '/foo/{required}/{optional?}' )
			             ->name( 'foo' )
			             ->and(['required', '\w+', 'optional', '\w+']);

			$url = $this->router->getRouteUrl( 'foo', ['required' => 'bar'] );
			$this->seeUrl( '/foo/bar/', $url );


			$this->router->get( '/bar/{required}/{optional?}' )
			             ->name( 'bar' )
			             ->and(['required' => '\w+', 'optional' =>  '\w+']);

			$url = $this->router->getRouteUrl( 'bar', ['required' => 'baz', 'optional' => 'biz'] );
			$this->seeUrl( '/bar/baz/biz/', $url );



			$this->router->get( '/foo/{required}/{optional1?}/{optional2?}' )
			             ->name( 'foobar' )
			             ->and(['required' => '\w+', 'optional1' =>  '\w+', 'optional2' =>  '\w+']);

			$url = $this->router->getRouteUrl( 'foobar', ['required' => 'bar', 'optional1' => 'baz', 'optional2' => 'biz'] );
			$this->seeUrl( '/foo/bar/baz/biz/', $url );




		}

		/** @test */
		public function missing_required_arguments_throw_an_exception () {

			$this->expectExceptionMessage('Required route segment: {required} missing');

			$this->router->get( 'foo/{required}' )->name( 'foo' );
			$url = $this->router->getRouteUrl( 'foo');


		}

		/** @test */
		public function an_exception_gets_thrown_if_the_passed_arguments_dont_satisfy_regex_constraints () {

			$this->expectExceptionMessage(
				'The provided value [bar] is not valid for the route: [foo]');

			$this->router->get( '/foo/{required}' )
			             ->name( 'foo' )
			             ->and(['required' => '\w+']);

			$this->router->getRouteUrl('foo', ['required' => '#']);

		}

		/** @test */
		public function custom_conditions_that_can_be_transformed_take_precedence_over_http_conditions () {

			add_menu_page('test', 'test', 'manage_options', 'test');

			$this->router->get( 'foo' )->name( 'foo_route' )->where('admin', 'test');
			$url = $this->router->getRouteUrl( 'foo_route' );
			$this->seeUrl( '/wp-admin/admin.php?page=test', $url );

		}


		private function seeUrl( $route_path, $result ) {

			$expected = rtrim(SITE_URL, '/') . '/' . ltrim($route_path, '/') ;

			// Strip https, http
			$expected = Str::after( $expected, '://' );
			$result   = Str::after( $result, '://' );

			$this->assertSame( $expected, $result );

		}


	}