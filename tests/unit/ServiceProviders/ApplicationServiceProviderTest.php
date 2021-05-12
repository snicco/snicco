<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Codeception\TestCase\WPTestCase;
	use Tests\stubs\TestApp;
	use WPEmerge\Facade\WP;
	use WpFacade\WpFacade;

	class ApplicationServiceProviderTest extends WPTestCase {

		use BootApplication;

		protected function setUp() : void {

			parent::setUp();

			$this->bootNewApplication(TEST_CONFIG);

		}

		protected function tearDown() : void {

			parent::tearDown();

			TestApp::setApplication(null);

		}

		/** @test */
		public function the_wp_facade_has_the_correct_container () {

			$container = TestApp::container();

			$this->assertSame($container, WpFacade::getFacadeContainer());

		}

		/** @test */
		public function calls_to_the_wordpress_api_facade_work () {

			$this->assertFalse(WP::isAdmin());

		}

		/** @test */
		public function the_facade_can_be_swapped_during_test () {

			WP::shouldReceive('isAdmin')->andReturn(true);

			$this->assertTrue(WP::isAdmin());

		}

	}