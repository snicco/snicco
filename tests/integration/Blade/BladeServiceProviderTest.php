<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\View\Compilers\BladeCompiler;
    use Illuminate\View\Engines\EngineResolver;
    use Illuminate\View\Factory;
    use Illuminate\View\FileViewFinder;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Blade\BladeEngine;
    use WPEmerge\Blade\BladeServiceProvider;
    use WPEmerge\Contracts\ViewEngineInterface;

    class BladeServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function the_blade_view_factory_is_bound_correctly () {

            $this->newTestApp([
                'providers'=> [
                    BladeServiceProvider::class
                ]
            ]);


            $this->assertInstanceOf(Factory::class, TestApp::resolve('view'));


        }

        /** @test */
        public function the_blade_view_finder_is_bound_correctly () {

            $this->newTestApp([
                'providers'=> [
                    BladeServiceProvider::class
                ]
            ]);


            $this->assertInstanceOf(FileViewFinder::class, TestApp::resolve('view.finder'));


        }

        /** @test */
        public function the_blade_compiler_is_bound_correctly () {

            $this->newTestApp([
                'providers'=> [
                    BladeServiceProvider::class
                ],
                'blade' => [
                    'cache' => '/Users/calvinalkan/valet/wpemerge/wpemerge/tests/integration/Blade/cache'
                ]
            ]);


            $this->assertInstanceOf(BladeCompiler::class, TestApp::resolve('blade.compiler'));


        }

        /** @test */
        public function the_engine_resolver_is_bound_correctly () {

            $this->newTestApp([
                'providers'=> [
                    BladeServiceProvider::class
                ],
            ]);


            $this->assertInstanceOf(EngineResolver::class, TestApp::resolve('view.engine.resolver'));

        }

        /** @test */
        public function the_view_service_now_uses_the_blade_engine () {

            $this->newTestApp([
                'providers'=> [
                    BladeServiceProvider::class
                ],
            ]);

            $this->assertInstanceOf(BladeEngine::class, TestApp::resolve(ViewEngineInterface::class));

        }



    }