<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Contracts\ContainerAdapter;
	use Mockery\MockInterface;
	use Tests\stubs\Foo;
	use Tests\stubs\TestApp;
	use Tests\Test;
	use WPEmerge\Facade\WordpressApi;
	use WPEmerge\Facade\WP;
	use WPEmerge\ServiceProviders\ApplicationServiceProvider;
	use WpFacade\WpFacade;

	class ApplicationServiceProviderTest extends Test {

		use BootServiceProviders;


		public function neededProviders() : array {

			return  [
				ApplicationServiceProvider::class,
			];
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