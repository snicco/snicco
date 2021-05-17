<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Blade\BladeEngine;
    use WPEmerge\Blade\BladeServiceProvider;
    use WPEmerge\Blade\BladeView;
    use WPEmerge\Contracts\ViewEngineInterface;

    class VIewComposingTest extends IntegrationTest
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

            $cache_dir = '/Users/calvinalkan/valet/wpemerge/wpemerge/tests/integration/Blade/cache';

            $this->rmdir($cache_dir);

            $this->newTestApp([
                'providers' => [
                    BladeServiceProvider::class,
                ],
                'blade' => [
                    'cache' => $cache_dir,
                    'views' => '/Users/calvinalkan/valet/wpemerge/wpemerge/tests/integration/Blade/views',
                ],
            ]);

        }

        private function makeView(string $view) {

            $view = $this->engine->make($view);
            return $view->toString();

        }

        /** @test */
        public function global_data_can_be_shared_with_all_views () {

            TestApp::globals()->add(['foo' => 'calvin']);

            $this->assertSame('calvin', $this->makeView('globals'));


        }

        /** @test */
        public function a_view_composer_can_be_added_to_a_view () {

            TestApp::addComposer('view-composer', function (BladeView $view ) {

                $view->with(['name' => 'calvin']);

            });

            $this->assertViewContent('calvin', $this->makeView('view-composer'));

        }



    }