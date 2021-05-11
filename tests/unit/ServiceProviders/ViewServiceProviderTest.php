<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Closure;
	use Codeception\TestCase\WPTestCase;
	use Tests\stubs\TestView;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFinder;
	use WPEmerge\View\ViewService;
	use WPEmerge\ViewComposers\ViewComposerCollection;

	class ViewServiceProviderTest extends WPTestCase {

		use BootApplication;

		protected function tearDown() : void {

			parent::tearDown();

			$this->reset();

		}

		/** @test */
		public function the_global_context_is_a_variable_bag_instance () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(VariableBag::class, $app->resolve('composers.globals'));

		}

		/** @test */
		public function the_view_service_is_resolved_correctly () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(ViewService::class, $app->resolve(ViewServiceInterface::class));

		}

		/** @test */
		public function the_view_composer_closure_is_resolved_correctly () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf( Closure::class, $c = $app->resolve('compose.callable'));

			$view = new TestView();

			$view = $c($view);

			$this->assertInstanceOf( ViewInterface::class, $view);

		}

		/** @test */
		public function the_view_finder_is_resolved_correctly () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(PhpViewFinder::class, $app->resolve(ViewFinderInterface::class));

		}

		/** @test */
		public function the_view_engine_is_resolved_correctly () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(PhpViewEngine::class, $app->resolve(ViewEngineInterface::class));

		}

		/** @test */
		public function the_view_composer_collection_is_resolved_correctly () {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf(ViewComposerCollection::class, $app->resolve(ViewComposerCollection::class));

		}





	}
