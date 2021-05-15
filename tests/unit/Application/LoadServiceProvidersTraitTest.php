<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Application;

    use Mockery;
    use Tests\BaseTestCase;
    use Tests\CreateDefaultWpApiMocks;
    use WPEmerge\Contracts\ServiceProvider;
	use Tests\stubs\TestApp;
	use Tests\stubs\TestProvider;
	use Tests\stubs\TestService;
    use WPEmerge\Facade\WP;

    class LoadServiceProvidersTraitTest extends BaseTestCase {

        use CreateDefaultWpApiMocks;

        protected function beforeTestRun()
        {

            WP::setFacadeContainer($this->createContainer());
            $this->setUpWp(VENDOR_DIR);


        }

        protected function beforeTearDown()
        {

            WP::setFacadeContainer(null);
            WP::clearResolvedInstances();
            Mockery::close();

        }


		/** @test */
		public function an_exception_gets_thrown_if_a_service_provider_doesnt_implement_the_correct_interface() {


			$this->expectExceptionMessage( 'The following class does not implement' );

			$app = new TestApp();

			$user_config = [

				'providers' => [
					TestService::class,
				],
			];

			$app::make();
			$app::boot( $user_config );


		}

		/** @test */
		public function the_register_method_gets_called_correctly_with_the_container_instance() {

			$app = new TestApp();

			$user_config = [

				'providers' => [
					TestProvider::class,
				],
			];

			$app::make();
			$app::boot( $user_config );

			$this->assertSame( 'bar', $app::container()['foo'] );


		}

		/** @test */
		public function the_boostrap_method_gets_called_correctly_with_the_container_instance() {

			$app = new TestApp();

			$user_config = [

				'providers' => [
					TestProvider::class,
				],
			];

			$app::make();
			$app::boot( $user_config );

			$this->assertSame( 'baz', $app::container()['bar'] );

		}

		/** @test */
		public function all_service_providers_share_the_same_configuration() {


			$app = new TestApp();

			$user_config = [

				'providers' => [
					Provider1::class,
					Provider2::class,
				],
			];

			$app::make();
			$app::boot( $user_config );

			$this->assertSame( 'bar', $app::resolve( 'config_value' ) );


		}

	}

	class Provider1 extends ServiceProvider {

		public function register() : void {

			$this->config['bar'] = 'bar';

		}

		function bootstrap() : void {

		}

	}

	class Provider2 extends ServiceProvider {

		public function register() : void {

			$value = $this->config['bar'] ?? 'wrong-value';
			$this->container->instance('config_value', $value);

		}

		function bootstrap() : void {


		}

	}
