<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Codeception\TestCase\WPTestCase;
	use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Http\ResponseFactory;
	use WPEmerge\Middleware\StartSession;
	use WPEmerge\Middleware\SubstituteBindings;

	class HttpServiceProviderTest extends WPTestCase {

		use BootApplication;

		/** @test */
		public function the_kernel_service_provider_can_be_resolved_correctly () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(HttpKernel::class, $app->resolve(HttpKernel::class));


		}

		/** @test */
		public function the_response_factory_can_be_resolved () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(ResponseFactory::class, $app->resolve(ResponseFactoryInterface::class));

		}

		/** @test */
		public function middleware_aliases_are_bound () {

			$app = $this->bootNewApplication();

			$aliases = $app->config('middleware.aliases', []);

			$this->assertArrayHasKey('csrf', $aliases);
			$this->assertArrayHasKey('auth', $aliases);
			$this->assertArrayHasKey('guest', $aliases);
			$this->assertArrayHasKey('can', $aliases);

		}

		/** @test */
		public function middleware_groups_are_bound () {

			$app = $this->bootNewApplication();

			$m_groups = $app->config('middleware.groups', []);

			$this->assertArrayHasKey('global', $m_groups);
			$this->assertArrayHasKey('web', $m_groups);
			$this->assertArrayHasKey('admin', $m_groups);
			$this->assertArrayHasKey('ajax', $m_groups);

		}

		/** @test */
		public function middleware_priority_is_set () {

			$app = $this->bootNewApplication();

			$priority = $app->config('middleware.priority', []);

			$this->assertContains(StartSession::class, $priority);
			$this->assertContains(SubstituteBindings::class, $priority);


		}



	}

