<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Tests\TestCase;
	use WPEmerge\Contracts\ResponseFactory;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Http\HttpResponseFactory;
	use WPEmerge\Middleware\StartSession;
	use WPEmerge\Middleware\SubstituteBindings;
	use WPEmerge\ServiceProviders\ExceptionServiceProvider;
	use WPEmerge\ServiceProviders\FactoryServiceProvider;
	use WPEmerge\ServiceProviders\HttpServiceProvider;
	use WPEmerge\ServiceProviders\RoutingServiceProvider;
	use WPEmerge\ServiceProviders\ViewServiceProvider;

	class HttpServiceProviderTest extends TestCase {

		use BootServiceProviders;

		public function neededProviders() : array {

			return [
				HttpServiceProvider::class,
				RoutingServiceProvider::class,
				FactoryServiceProvider::class,
				ExceptionServiceProvider::class,
				ViewServiceProvider::class,
			];

		}

		protected function tearDown() : void {


		}

		/** @test */
		public function the_kernel_can_be_resolved_correctly() {


			$this->assertInstanceOf( HttpKernel::class, $this->app->resolve( HttpKernel::class ) );


		}

		/** @test */
		public function the_response_factory_can_be_resolved() {


			$this->assertInstanceOf( HttpResponseFactory::class, $this->app->resolve( ResponseFactory::class ) );

		}

		/** @test */
		public function middleware_aliases_are_bound() {

			$aliases = $this->config->get( 'middleware.aliases', [] );

			$this->assertArrayHasKey( 'csrf', $aliases );
			$this->assertArrayHasKey( 'auth', $aliases );
			$this->assertArrayHasKey( 'guest', $aliases );
			$this->assertArrayHasKey( 'can', $aliases );

		}

		/** @test */
		public function middleware_groups_are_bound() {

			$m_groups = $this->config->get( 'middleware.groups', [] );

			$this->assertArrayHasKey( 'global', $m_groups );
			$this->assertArrayHasKey( 'web', $m_groups );
			$this->assertArrayHasKey( 'admin', $m_groups );
			$this->assertArrayHasKey( 'ajax', $m_groups );

		}

		/** @test */
		public function middleware_priority_is_set() {


			$priority = $this->config->get( 'middleware.priority', [] );

			$this->assertContains( StartSession::class, $priority );
			$this->assertContains( SubstituteBindings::class, $priority );


		}


	}

