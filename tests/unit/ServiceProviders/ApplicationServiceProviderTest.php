<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Codeception\TestCase\WPTestCase;
	use Contracts\ContainerAdapter;
	use Mockery\MockInterface;
	use Tests\stubs\Foo;
	use Tests\stubs\TestApp;
	use WPEmerge\Facade\WordpressApi;
	use WPEmerge\Facade\WP;
	use WpFacade\WpFacade;

	class ApplicationServiceProviderTest extends WPTestCase {

		use BootApplication;

		protected function setUp() : void {

			parent::setUp();

			$this->bootNewApplication( TEST_CONFIG );

		}



		/** @test */
		public function the_wp_facade_has_the_correct_container() {

			$container = TestApp::container();

			$this->assertSame( $container, WpFacade::getFacadeContainer() );

		}

		/** @test */
		public function calls_to_the_wordpress_api_facade_work() {

			$result = WP::isAdmin();

			$this->assertIsBool( $result );


		}

		/** @test */
		public function the_facade_can_be_swapped_during_test() {

			WP::shouldReceive( 'isAdmin' )->andReturn( true );

			$this->assertTrue( WP::isAdmin() );

		}

		/** @test */
		public function the_wp_api_can_be_mocked_with_the_configuration() {

			$this->bootNewApplication( array_merge( TEST_CONFIG, [
				'testing' => [
					'enabled' => true,
				],
			] ) );

			$this->assertInstanceOf( MockInterface::class, TestApp::resolve( WordpressApi::class ) );

		}

		/** @test */
		public function for_advanced_testing_cases_a_callable_can_be_passed_which_can_be_used_to_set_up_the_wordpress_api () {

			$this->bootNewApplication( array_merge( TEST_CONFIG, [
				'testing' => [
					'enabled' => true,
					'callable' => [ $this, 'manipulateWpApi' ]
				],
			]));

			$this->assertInstanceOf(Foo::class, TestApp::resolve(WordpressApi::class));


		}

		public function manipulateWpApi (ContainerAdapter $container) {

			return new Foo();

		}

	}