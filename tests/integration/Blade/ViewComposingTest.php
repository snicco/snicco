<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Snicco\Blade\BladeEngine;
    use Snicco\Blade\BladeView;
    use Snicco\Contracts\ViewEngineInterface;
    use Tests\stubs\TestApp;

    class ViewComposingTest extends BladeTestCase
    {


        /**
         * @var BladeEngine
         */
        private $engine;

        protected function setUp() : void
        {


            $this->afterApplicationCreated(function () {
                $this->engine = TestApp::resolve(ViewEngineInterface::class);
            });
            parent::setUp();


        }


        private function makeView(string $view) {

            $view = $this->engine->make($view);
            return $view->toString();

        }

        /** @test */
        public function global_data_can_be_shared_with_all_views () {

            TestApp::globals('globals', ['foo' => 'calvin']);

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