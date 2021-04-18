<?php


	namespace WPEmergeTests\unit\ServiceProviders;

	use Mockery as m;
	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Application\Application;
	use WPEmerge\Flash\Flash;
	use WPEmerge\Flash\FlashMiddleware;
	use WPEmerge\ServiceProviders\FlashServiceProvider;

	class FlashServiceProviderTest extends TestCase {


		/** @test */
		public function the_flash_service_is_bound_as_a_singleton_and_the_session_is_passed_as_a_reference() {

			$app = m::spy( Application::class );

			$container = new BaseContainerAdapter();

			$_SESSION = ['foo','bar'];

			$container->singleton( WPEMERGE_APPLICATION_KEY, function () use ($app) {

				return $app;

			});

			$subject = new FlashServiceProvider();

			$subject->register($container);

			$flash = $container->make(WPEMERGE_FLASH_KEY);

			$this->assertInstanceOf(Flash::class, $flash);

			$expected = ['foo', 'bar', '__wpemergeFlash' => ['current' => [], 'next' => [] ]];

			$this->assertSame( $expected , $flash->getStore());

			$_SESSION = ['foo','bar', 'baz'];

			// This because we are passing by reference and not by value.
			$this->assertSame( ['foo','bar', 'baz'] , $flash->getStore());


		}

		/** @test */
		public function the_flash_service_can_be_created_with_a_custom_replacement() {

			$app = m::spy( Application::class );

			$container = new BaseContainerAdapter();

			$container->singleton( WPEMERGE_APPLICATION_KEY, function () use ($app) {

				return $app;

			});
			$container->singleton( WPEMERGE_SESSION_KEY, function () use ($app) {

				return new CustomFlashStorage();

			});

			$subject = new FlashServiceProvider();

			$subject->register($container);

			$flash = $container->make(WPEMERGE_FLASH_KEY);

			$this->assertInstanceOf(Flash::class, $flash);

			$this->assertInstanceOf( CustomFlashStorage::class, $flash->getStore());




		}

		/** @test */
		public function the_flash_middleware_gets_bound_correctly () {

			$_SESSION = ['foo','bar'];

			$app = m::spy( Application::class );

			$container = new BaseContainerAdapter();

			$container->singleton( WPEMERGE_APPLICATION_KEY, function () use ($app) {

				return $app;

			});

			$subject = new FlashServiceProvider();

			$subject->register($container);

			$middleware1 = $container->make(FlashMiddleware::class );
			$middleware2 = $container->make(FlashMiddleware::class );

			$this->assertSame($middleware1, $middleware2);

			$app->shouldHaveReceived('alias')->once()->with('flash', WPEMERGE_FLASH_KEY);


		}

	}

	class CustomFlashStorage implements \ArrayAccess {


		public function offsetExists( $offset ) {
			// TODO: Implement offsetExists() method.
		}

		public function offsetGet( $offset ) {
			// TODO: Implement offsetGet() method.
		}

		public function offsetSet( $offset, $value ) {
			// TODO: Implement offsetSet() method.
		}

		public function offsetUnset( $offset ) {
			// TODO: Implement offsetUnset() method.
		}

	}
