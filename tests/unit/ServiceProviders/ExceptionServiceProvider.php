<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Codeception\TestCase\WPTestCase;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Exceptions\DebugErrorHandler;
	use WPEmerge\Exceptions\NullErrorHandler;
	use WPEmerge\Exceptions\ProductionErrorHandler;

	class ExceptionServiceProvider extends WPTestCase {

		use BootApplication;

		/** @test */
		public function by_default_the_inbuilt_production_handler_is_used_if_enabled () {

			$app = $this->bootNewApplication();

			$this->assertSame(ProductionErrorHandler::class, $app->resolve(ProductionErrorHandler::class));

		}

		/** @test */
		public function by_default_the_null_error_handler_is_used () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(
				NullErrorHandler::class,
				$app->resolve(ErrorHandlerInterface::class)
			);

		}

		/** @test */
		public function exception_handling_can_be_enabled_with_the_config () {

			$app = $this->bootNewApplication(['exception_handling.enable' => true]);

			$this->assertInstanceOf(
				ProductionErrorHandler::class,
				$app->resolve(ErrorHandlerInterface::class)
			);

		}

		/** @test */
		public function debug_exception_handling_can_be_set_with_the_config () {

			$app = $this->bootNewApplication(['exception_handling.enable' => true, 'exception_handling.debug' => true ]);

			$this->assertInstanceOf(
				DebugErrorHandler::class,
				$app->resolve(ErrorHandlerInterface::class)
			);

		}

		/** @test */
		public function the_production_error_handler_can_be_overwritten () {

			$app = $this->bootNewApplication([
				'providers' => [
					MyExceptionServiceProvider::class
				],
				'exception_handling' => [
					'enable' => true,
				]
			]);

			$this->assertInstanceOf(MyProductionErrorHandler::class, $app->resolve(ErrorHandlerInterface::class));

		}

	}

	class MyProductionErrorHandler extends ProductionErrorHandler {


	}

	class MyExceptionServiceProvider extends ServiceProvider {

		public function register() : void {

			$this->container->instance(
				ProductionErrorHandler::class,
				MyProductionErrorHandler::class
			);

		}

		function bootstrap() : void {

		}

	}
