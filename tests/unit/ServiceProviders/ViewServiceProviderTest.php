<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Closure;
	use Codeception\TestCase\WPTestCase;
	use Tests\stubs\TestView;
	use Tests\TestCase;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\ServiceProviders\FactoryServiceProvider;
	use WPEmerge\ServiceProviders\ViewServiceProvider;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFinder;
	use WPEmerge\View\ViewService;
	use WPEmerge\ViewComposers\ViewComposerCollection;

	class ViewServiceProviderTest extends TestCase {

		use BootServiceProviders;

		public function neededProviders() : array {

			return [
				ViewServiceProvider::class,
				FactoryServiceProvider::class,
			];

		}

		/** @test */
		public function the_global_context_is_a_variable_bag_instance () {


			$this->assertInstanceOf(VariableBag::class, $this->app->resolve('composers.globals'));

		}

		/** @test */
		public function the_view_service_is_resolved_correctly () {

			$this->assertInstanceOf(ViewService::class, $this->app->resolve(ViewServiceInterface::class));

		}

		/** @test */
		public function the_view_finder_is_resolved_correctly () {



			$this->assertInstanceOf(PhpViewFinder::class, $this->app->resolve(ViewFinderInterface::class));

		}

		/** @test */
		public function the_view_engine_is_resolved_correctly () {

			$this->assertInstanceOf(PhpViewEngine::class, $this->app->resolve(ViewEngineInterface::class));

		}

		/** @test */
		public function the_view_composer_collection_is_resolved_correctly () {



			$this->assertInstanceOf(ViewComposerCollection::class, $this->app->resolve(ViewComposerCollection::class));

		}




	}
