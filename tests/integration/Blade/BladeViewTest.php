<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Tests\integration\Blade\traits\AssertBladeView;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Blade\BladeEngine;
    use WPEmerge\Blade\BladeServiceProvider;
    use WPEmerge\Contracts\ViewEngineInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ViewException;

    class BladeViewTest extends IntegrationTest
    {

        use AssertBladeView;

        /**
         * @var BladeEngine
         */
        private $engine;

        protected function setUp() : void
        {

            parent::setUp();

            $this->newApp();

            $this->engine = TestApp::resolve(ViewEngineInterface::class);

        }

        private function newApp()
        {

            $cache_dir = TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'cache';

            $this->rmdir($cache_dir);

            $this->newTestApp([
                'providers' => [
                    BladeServiceProvider::class,
                ],
                'blade' => [
                    'cache' => $cache_dir,
                    'views' => TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'views'
                ],
            ]);

        }

        /** @test */
        public function a_blade_view_can_be_rendered()
        {

            $view = $this->engine->make('foo');

            $this->assertViewContent('FOO', $view->toString());


        }

        /** @test */
        public function a_blade_view_can_be_transformed_to_a_responsable()
        {

            $view = $this->engine->make('foo');

            $this->assertViewContent('FOO', $view->toResponsable());

        }

        /** @test */
        public function variables_can_be_shared_with_a_view()
        {

            $view = $this->engine->make('variables');
            $view->with('name', 'calvin');

            $this->assertViewContent('hello calvin', $view->toString());


        }

        /** @test */
        public function view_errors_are_caught()
        {

            $this->expectException(ViewException::class);

            $view = $this->engine->make('variables');
            $view->with('bogus', 'calvin');

            $this->assertViewContent('hello calvin', $view->toString());

        }

        /** @test */
        public function blade_internals_are_included_in_the_view()
        {

            $view = $this->engine->make('internal');

            $this->assertViewContent('app:env', $view->toString());

        }

        /** @test */
        public function the_view_service_can_render_the_blade_view () {

            ob_start();

            TestApp::render('variables', ['name'=>'calvin']);

            $html = ob_get_clean();

            $this->assertViewContent('hello calvin', $html);


        }


    }