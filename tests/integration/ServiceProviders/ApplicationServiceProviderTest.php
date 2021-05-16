<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
	use WPEmerge\Facade\WP;
	use WpFacade\WpFacade;

	class ApplicationServiceProviderTest extends IntegrationTest {


	    protected function setUp() : void
        {

            parent::setUp();

            $this->newTestApp();

        }



		/** @test */
		public function the_wp_facade_has_the_correct_container() {

			$container = TestApp::container();

			$this->assertSame( $container, WpFacade::getFacadeContainer() );

		}

		/** @test */
		public function the_facade_can_be_swapped_during_test() {

			WP::shouldReceive( 'isAdmin' )->andReturn( true );

			$this->assertTrue( WP::isAdmin() );

		}



	}