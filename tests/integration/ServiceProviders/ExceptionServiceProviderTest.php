<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\ExceptionHandling\DebugErrorHandler;
	use WPEmerge\ExceptionHandling\NullErrorHandler;
	use WPEmerge\ExceptionHandling\ProductionErrorHandler;

    class ExceptionServiceProviderTest extends IntegrationTest {



        /** @test */
		public function by_default_the_null_error_handler_is_used() {

            $this->newTestApp();

            $this->assertInstanceOf(
				NullErrorHandler::class,
				TestApp::resolve( ErrorHandlerInterface::class )
			);

		}

		/** @test */
		public function if_not_overwritten_the_default_production_error_handler_will_be_used() {

            $this->newTestApp();

			$this->assertSame(
				ProductionErrorHandler::class,
                TestApp::resolve( ProductionErrorHandler::class )
			);

		}

		/** @test */
		public function debug_exception_handling_can_be_set_with_the_config() {

            $this->newTestApp([
                'exception_handling' => [
                    'enable' => true,
                    'debug'  => true,
                ]
            ]);


			$this->assertInstanceOf(
				DebugErrorHandler::class,
				TestApp::resolve( ErrorHandlerInterface::class )
			);

		}

		/** @test */
		public function the_production_error_handler_can_be_overwritten_from_a_service_provider() {

            $this->newTestApp([
                'exception_handling' => [
                    'enable' => true,
                ],
                'providers' => [
                    MyProvider::class
                ]
            ]);


			$this->assertInstanceOf(
				MyProductionErrorHandler::class,
				TestApp::resolve( ErrorHandlerInterface::class )
			);

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
            // TODO: Implement bootstrap() method.
        }

    }

	class MyProductionErrorHandler extends ProductionErrorHandler {


	}


