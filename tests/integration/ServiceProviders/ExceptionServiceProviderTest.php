<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\TestCase;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\ExceptionHandling\DebugErrorHandler;
	use WPEmerge\ExceptionHandling\NullErrorHandler;
	use WPEmerge\ExceptionHandling\ProductionErrorHandler;

    class ExceptionServiceProviderTest extends TestCase {


        protected $defer_boot = true;

        /** @test */
		public function by_default_the_production_error_handler_is_used() {

            $this->boot();

            $this->assertInstanceOf(
				ProductionErrorHandler::class,
				TestApp::resolve( ErrorHandlerInterface::class )
			);

            // ! a FQN is bound here. Used in the ErrorHandlerFactory
            $this->assertSame(
                ProductionErrorHandler::class,
                TestApp::resolve( ProductionErrorHandler::class )
            );


		}

		/** @test */
		public function the_null_error_handler_can_be_used () {

            $this->withAddedConfig('app.exception_handling', false)->boot();

            $this->assertInstanceOf(
                NullErrorHandler::class,
                TestApp::resolve( ErrorHandlerInterface::class )
            );

		}


		/** @test */
		public function the_production_error_handler_can_be_overwritten_from_a_service_provider() {


		    $this->withAddedProvider(MyProvider::class)->boot();


			$this->assertInstanceOf(
				MyProductionErrorHandler::class,
				TestApp::resolve( ErrorHandlerInterface::class )
			);

		}

        /** @test */
        public function the_error_views_are_registered () {

            $this->boot();
            $views = TestApp::config('view.paths');
            $expected = ROOT_DIR.DS.'src'.DS.'ExceptionHandling'.DS.'views';

            $this->assertContains($expected, $views);

        }


	}

	class MyProvider extends ServiceProvider {


        public function register() : void
        {
            $this->container->instance(
                ProductionErrorHandler::class,
                MyProductionErrorHandler::class
            );
        }

        function bootstrap() : void
        {
        }

    }

	class MyProductionErrorHandler extends ProductionErrorHandler {


	}


