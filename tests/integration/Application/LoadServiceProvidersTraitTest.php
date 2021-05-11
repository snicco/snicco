<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Application;

	use Codeception\TestCase\WPTestCase;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\ServiceProviders\AliasServiceProvider;
	use WPEmerge\ServiceProviders\ApplicationServiceProvider;
	use WPEmerge\ServiceProviders\EventServiceProvider;
	use WPEmerge\ServiceProviders\ExceptionsServiceProvider;
	use WPEmerge\ServiceProviders\FactoryServiceProvider;
	use WPEmerge\ServiceProviders\KernelServiceProvider;
	use WPEmerge\ServiceProviders\RequestsServiceProvider;
	use WPEmerge\ServiceProviders\ResponsesServiceProvider;
	use WPEmerge\ServiceProviders\RoutingServiceProvider;
	use WPEmerge\ServiceProviders\ViewServiceProvider;
	use Tests\stubs\TestApp;
	use Tests\stubs\TestProvider;
	use Tests\stubs\TestService;

	class LoadServiceProvidersTraitTest extends WPTestCase {

		private $service_providers = [

			AliasServiceProvider::class,
			FactoryServiceProvider::class,
			ApplicationServiceProvider::class,
			KernelServiceProvider::class,
			ExceptionsServiceProvider::class,
			RequestsServiceProvider::class,
			ResponsesServiceProvider::class,
			RoutingServiceProvider::class,
			ViewServiceProvider::class,
			EventServiceProvider::class,

		];

		/** @test */
		public function all_core_service_providers_get_registered_correctly_in_the_container_and_can_be_merged_with_user_provided_ones() {

			$app = new TestApp();

			$user_config = [

				'providers' => [
					TestProvider::class,
				],
			];

			$app::make();
			$app::boot( $user_config );

			$container = $app::container();

			foreach ( $this->service_providers as $service_provider ) {

				$this->assertTrue( $container->offsetExists( $service_provider ), $service_provider . ' not found in container.' );

			}

			$this->assertTrue( $container->offsetExists( TestProvider::class ) );


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

			$this->assertSame( 'bar', $app::resolve('config_value') );


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

