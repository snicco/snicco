<?php


	namespace WPEmergeTests\wpunit\Application;

	use Codeception\TestCase\WPTestCase;
	use Contracts\ContainerAdapter;
	use WPEmerge\Application\Application;
	use WPEmerge\Application\ApplicationServiceProvider;
	use WPEmerge\Controllers\ControllersServiceProvider;
	use WPEmerge\Csrf\CsrfServiceProvider;
	use WPEmerge\Exceptions\ExceptionsServiceProvider;
	use WPEmerge\Flash\FlashServiceProvider;
	use WPEmerge\Input\OldInputServiceProvider;
	use WPEmerge\Kernels\KernelsServiceProvider;
	use WPEmerge\Middleware\MiddlewareServiceProvider;
	use WPEmerge\Requests\RequestsServiceProvider;
	use WPEmerge\Responses\ResponsesServiceProvider;
	use WPEmerge\Routing\RoutingServiceProvider;
	use WPEmerge\ServiceProviders\ServiceProviderInterface;
	use WPEmerge\View\ViewServiceProvider;
	use WPEmergeTests\TestApp;
	use WPEmergeTests\wpunit\TestingApp;

	class LoadServiceProvidersTraitTest extends WPTestCase {

		private $service_providers = [
			ApplicationServiceProvider::class,
			KernelsServiceProvider::class,
			ExceptionsServiceProvider::class,
			RequestsServiceProvider::class,
			ResponsesServiceProvider::class,
			RoutingServiceProvider::class,
			ViewServiceProvider::class,
			ControllersServiceProvider::class,
			MiddlewareServiceProvider::class,
			CsrfServiceProvider::class,
			FlashServiceProvider::class,
			OldInputServiceProvider::class,
		];

		/** @test */
		public function all_core_service_providers_get_registered_correctly_in_the_container_and_can_be_merged_with_user_provided_ones() {

			$app = new TestingApp();

			$user_config = [

				'providers' => [
					TestProvider1::class,
					TestProvider2::class,
				]
			];

			$app::bootstrap($user_config);

			$container = $app::container();



			foreach ( array_merge($this->service_providers, $user_config['providers']) as $service_provider ) {

				$this->assertTrue($container->offsetExists($service_provider));

			}


		}

	}


	class TestProvider1 implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {
			// TODO: Implement register() method.
		}

		public function bootstrap( ContainerAdapter $container ) {
			// TODO: Implement bootstrap() method.
		}

	}


	class TestProvider2 implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {
			// TODO: Implement register() method.
		}

		public function bootstrap( ContainerAdapter $container ) {
			// TODO: Implement bootstrap() method.
		}

	}