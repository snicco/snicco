<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Application;

	use Codeception\TestCase\WPTestCase;
    use Mockery\Exception\InvalidArgumentException;
    use Psr\Http\Message\ServerRequestInterface;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\CreateContainer;
    use Tests\stubs\TestContainer;
	use WPEmerge\Application\Application;
	use WPEmerge\Application\ApplicationConfig;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Exceptions\ConfigurationException;
    use WPEmerge\Http\Request;

    class ApplicationTest extends WPTestCase {

        use CreateContainer;

		/** @test */
		public function the_static_constructor_returns_an_application_instance() {

			$base_container = new BaseContainerAdapter();

			$application = Application::create( $base_container );

			$this->assertInstanceOf( Application::class, $application );

			$this->assertSame( $base_container, $application->container() );

		}

		/** @test */
		public function the_application_cant_be_bootstrapped_twice() {

			$app = $this->newApplication();

			try {

				$app->boot( [] );

			}

			catch ( \Throwable $e ) {

				$this->fail( 'Application could not be bootstrapped.' . PHP_EOL . $e->getMessage() );

			}

			try {

				$app->boot( [] );

				$this->fail( 'Application was bootstrapped two times.' );

			}

			catch ( ConfigurationException $e ) {

				$this->assertStringContainsString( 'already bootstrapped', $e->getMessage() );

			}


		}

		/** @test */
		public function user_provided_config_gets_bound_into_the_di_container() {

			$app = $this->newApplication();

			$app->boot( [ 'foo' => 'bar' ] );

			$this->assertEquals( 'bar', $app->config( 'foo' ) );


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

			$container = new TestContainer();

			$app = new Application( $container );

			$this->assertSame( $container, $app->container() );
			$this->assertInstanceOf( TestContainer::class, $app->container() );


		}

		/** @test */
		public function config_values_can_be_retrieved () {

			$app = $this->newApplication();
			$app->boot(['foo' => 'bar', 'bar' => ['baz'=>'boo']]);

			$this->assertInstanceOf(
				ApplicationConfig::class,
				$app->resolve(ApplicationConfig::class)
			);
			$this->assertSame('bar', $app->config('foo'));
			$this->assertSame('boo', $app->config('bar.baz'));
			$this->assertSame('bogus_default', $app->config('bogus', 'bogus_default'));



		}

		/** @test */
		public function the_application_run_configuration_can_be_set_from_the_config () {

			$app1 = $this->newApplication();
			$app1->boot();

			$this->assertFalse($app1->isTakeOverMode());

			$app2 = $this->newApplication();
			$app2->boot(['strict_mode' => true]);

			$this->assertTrue($app2->isTakeOverMode());

		}

		/** @test */
		public function the_request_is_captured () {

            $app1 = $this->newApplication();

            $this->assertInstanceOf(Request::class, $app1->resolve(Request::class));

		}

		/** @test */
		public function a_custom_server_request_can_be_provided () {

		    $app = new Application(  $this->createContainer(), $mock = \Mockery::mock(ServerRequestInterface::class) );

		    $this->assertInstanceOf(Request::class, $app->resolve(Request::class));

		}

		private function newApplication() : Application {

            return new Application(  $this->createContainer() );
		}


	}

	class UserServiceProvider extends ServiceProvider{


		public function register() :void {

			$this->container->instance('foo', 'bar');

		}

		public function bootstrap(  ) :void {

			$this->container->instance('foo_bootstrapped', 'bar_bootstrapped');

		}

	}