<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Application;

	use Codeception\TestCase\WPTestCase;
	use Contracts\ContainerAdapter;
	use Mockery as m;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Application\Application;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Exceptions\ConfigurationException;

	class ApplicationTest extends WPTestCase {



		public function tearDown() :void {

			m::close();
			parent::tearDown();

		}


		/** @test */
		public function the_static_constructor_returns_an_application_instance() {

			$base_container = new BaseContainerAdapter();

			$application = Application::create($base_container);

			$this->assertInstanceOf( Application::class, $application );

			$this->assertSame($base_container, $application->container());

		}

		/** @test */
		public function the_application_cant_be_bootstrapped_twice() {

			$app = $this->newApplication();

			try {

				$app->boot( [] );

			}

			catch (\Throwable $e ) {

				$this->fail('Application could not be bootstrapped.' . PHP_EOL . $e->getMessage() );

			}

			try {

				$app->boot( [] );

				$this->fail('Application was bootstrapped two times.');

			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString('already bootstrapped', $e->getMessage());

			}



		}

		/** @test */
		public function user_provided_config_gets_bound_into_the_di_container() {

			$app = $this->newApplication();


			$app->boot([ 'foo' => 'bar']);

			$this->assertEquals('bar', $app->config('foo', null ));



		}

		/** @test */
		public function users_can_register_service_providers() {

			$app = $this->newApplication();

			$app->boot(
				[ 'providers' => [ UserServiceProvider::class, ], ],
			);

			$this->assertEquals( 'bar', $app->container()['foo'] );
			$this->assertEquals( 'bar_bootstrapped', $app->container()['foo_bootstrapped'] );


		}

		/** @test */
		public function custom_container_adapters_can_be_used() {

			$container = m::mock(ContainerAdapter::class );
			$container->shouldIgnoreMissing();

			$app = new Application($container);

			$this->assertSame($container, $app->container());


		}


		private function newApplication() : Application {

			return new Application(new BaseContainerAdapter());

		}







	}

	class UserServiceProvider extends ServiceProvider{


		public function register(  ) :void {

			$this->container->instance('foo', 'bar');

		}

		public function bootstrap(  ) :void {

			$this->container->instance('foo_bootstrapped', 'bar_bootstrapped');

		}

	}






