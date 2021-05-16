<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use Mockery;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ServiceProvider;
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

		/** @test */
		public function the_error_handler_gets_unregistered_by_default_after_booting_the_app () {

		    $this->newTestApp([
		        'providers'=> [
		            NoGlobalExceptions::class,
                ]
            ]);

		    $this->assertTrue(true);

		    Mockery::close();


		}

        /** @test */
		public function the_error_handler_can_be_registered_globally () {

		    $this->newTestApp([
		        'providers'=> [
		            GlobalExceptions::class,
                ],
                'exception_handling' => [
                    'global' => true
                ]
            ]);

		    $this->assertTrue(true);

            Mockery::close();


        }


	}

	class NoGlobalExceptions extends ServiceProvider {

        public function register() : void
        {

            $mock = Mockery::mock(ErrorHandlerInterface::class);

            $mock->shouldReceive('register')->once();
            $mock->shouldReceive('unregister')->once();

            $this->container->instance(ErrorHandlerInterface::class, $mock);

        }

        function bootstrap() : void
        {
            // TODO: Implement bootstrap() method.
        }

    }

	class GlobalExceptions extends ServiceProvider {

        public function register() : void
        {

            $mock = Mockery::mock(ErrorHandlerInterface::class);

            $mock->shouldReceive('register')->once();
            $mock->shouldNotReceive('unregister');

            $this->container->instance(ErrorHandlerInterface::class, $mock);

        }

        function bootstrap() : void
        {
            // TODO: Implement bootstrap() method.
        }

    }