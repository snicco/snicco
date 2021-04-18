<?php


	namespace WPEmergeTests\unit\ServiceProviders;

	use Mockery as m;
	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Application\Application;
	use WPEmerge\Flash\Flash;
	use WPEmerge\Input\OldInput;
	use WPEmerge\Input\OldInputMiddleware;
	use WPEmerge\ServiceProviders\OldInputServiceProvider;

	class OldInputServiceProviderTest extends TestCase {


		/** @test */
		public function all_old_input_services_get_created_correctly_as_singletons() {

			$flash = m::mock( Flash::class );
			$app = m::spy( Application::class );
			$container = new BaseContainerAdapter();
			$container->singleton( WPEMERGE_FLASH_KEY, function ( $c ) use ( $flash ) {

				return $flash;

			} );
			$container->singleton( WPEMERGE_APPLICATION_KEY, function ( $c ) use ( $app ) {

				return $app;

			} );


			$subject = new OldInputServiceProvider();

			$subject->register( $container );

			$old_input1 = $container->make( WPEMERGE_OLD_INPUT_KEY );
			$old_input2 = $container->make( WPEMERGE_OLD_INPUT_KEY );

			$this->assertSame( $old_input1, $old_input2 );
			$this->assertInstanceOf( OldInput::class, $old_input1 );

			$old_input_middleware_1 = $container->make( OldInputMiddleware::class );
			$old_input_middleware_2 = $container->make( OldInputMiddleware::class );

			$this->assertSame( $old_input_middleware_1, $old_input_middleware_2 );
			$this->assertInstanceOf( OldInputMiddleware::class, $old_input_middleware_1 );


			$app->shouldHaveReceived('alias')->once()->with('oldInput', WPEMERGE_OLD_INPUT_KEY);

		}


	}
