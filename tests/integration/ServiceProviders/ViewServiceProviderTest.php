<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewFactoryInterface;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFinder;
	use WPEmerge\View\ViewFactory;
	use WPEmerge\View\ViewComposerCollection;

	class ViewServiceProviderTest extends IntegrationTest {


		/** @test */
		public function the_global_context_is_a_variable_bag_instance () {

		    $this->newTestApp();

			$this->assertInstanceOf(VariableBag::class, TestApp::resolve('composers.globals'));

		}

		/** @test */
		public function the_view_service_is_resolved_correctly () {

            $this->newTestApp();

            $this->assertInstanceOf(ViewFactory::class, TestApp::resolve(ViewFactoryInterface::class));

		}

		/** @test */
		public function the_view_engine_is_resolved_correctly () {

            $this->newTestApp();

            $this->assertInstanceOf(PhpViewEngine::class, TestApp::resolve(ViewEngineInterface::class));

		}

		/** @test */
		public function the_view_composer_collection_is_resolved_correctly () {

            $this->newTestApp();

			$this->assertInstanceOf(ViewComposerCollection::class, TestApp::resolve(ViewComposerCollection::class));

		}


	}
