<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Tests\TestCase;
	use Tests\TestRequest;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Exceptions\DebugErrorHandler;
	use WPEmerge\Exceptions\NullErrorHandler;
	use WPEmerge\Exceptions\ProductionErrorHandler;
	use WPEmerge\ServiceProviders\ExceptionServiceProvider;

	class ExceptionServiceProviderTest extends TestCase {

		use BootServiceProviders;

		public function neededProviders() : array {

			return [
				ExceptionServiceProvider::class,
			];
		}


		/** @test */
		public function by_default_the_null_error_handler_is_used() {

			$this->assertInstanceOf(
				NullErrorHandler::class,
				$this->app->resolve( ErrorHandlerInterface::class )
			);

		}

		/** @test */
		public function by_default_the_inbuilt_production_handler_is_used_if_enabled() {

			$this->config->set( 'exception_handling.enable', true );

			$this->app->container()->instance(
				RequestInterface::class,
				TestRequest::from( 'GET', '/foo' )
			);

			$this->assertSame(
				ProductionErrorHandler::class,
				$this->app->resolve( ProductionErrorHandler::class )
			);

		}

		/** @test */
		public function debug_exception_handling_can_be_set_with_the_config() {

			$this->config->set( [
				'exception_handling.enable' => true,
				'exception_handling.debug'  => true,
			] );

			$this->assertInstanceOf(
				DebugErrorHandler::class,
				$this->app->resolve( ErrorHandlerInterface::class )
			);

		}

		/** @test */
		public function the_production_error_handler_can_be_overwritten() {

			$this->config->set( 'exception_handling.enable', true );

			$this->app->container()->instance(
				ProductionErrorHandler::class,
				MyProductionErrorHandler::class
			);

			$this->assertInstanceOf(
				MyProductionErrorHandler::class,
				$this->app->resolve( ErrorHandlerInterface::class )
			);

		}

	}


	class MyProductionErrorHandler extends ProductionErrorHandler {


	}


