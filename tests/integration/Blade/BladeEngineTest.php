<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Blade\BladeServiceProvider;
    use WPEmerge\Blade\BladeView;
    use WPEmerge\ExceptionHandling\Exceptions\ViewException;
    use WPEmerge\ExceptionHandling\Exceptions\ViewNotFoundException;
    use WPEmerge\View\ViewFactory;

    class BladeEngineTest extends IntegrationTest
    {

        /** @test */
        public function the_blade_factory_can_create_a_blade_view () {

            $this->newTestApp([
                'providers'=> [
                    BladeServiceProvider::class
                ],
                'blade' => [
                    'cache' => TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'cache',
                    'views' => TESTS_DIR.DS.'integration'.DS.'Blade'.DS.'views'
                ]
            ]);

            /** @var ViewFactory $view_service */
            $view_service = TestApp::resolve(ViewFactory::class);

            $this->assertInstanceOf(BladeView::class, $view_service->make('foo') );

        }

        /** @test */
        public function exceptions_get_caught_and_translated () {

            $this->newTestApp([
                'providers'=> [
                    BladeServiceProvider::class
                ],
                'blade' => [
                    'cache' => '/Users/calvinalkan/valet/wpemerge/wpemerge/tests/integration/Blade/cache',
                    'views' => '/Users/calvinalkan/valet/wpemerge/wpemerge/tests/integration/Blade/views',
                ]
            ]);

            /** @var ViewFactory $view_service */
            $view_service = TestApp::resolve(ViewFactory::class);

            $this->expectException(ViewNotFoundException::class);

            $this->assertInstanceOf(BladeView::class, $view_service->make('bogus') );

        }


    }