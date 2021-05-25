<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\stubs\TestResponseEmitter;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Response;
    use WPEmerge\Routing\Router;

    class ViewRoutesTest extends UnitTest
    {

        use TestHelpers;
        use CreateDefaultWpApiMocks;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        /** @var TestResponseEmitter */
        private $emitter;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

            $this->createBindingsForViewController();


        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function view_routes_work()
        {

            $this->createRoutes(function () {

                $this->router->view('/foo', 'welcome.wordpress');


            });

            $request = $this->webRequest('GET', '/foo');

            $this->runAndAssertOutput('VIEW:welcome.wordpress,CONTEXT:[]', $request);

            $this->assertContains('Content-Type: text/html', $this->emitter->headers);
            $this->assertStringContainsString('200', $this->emitter->status_line);

        }

        /** @test */
        public function the_default_values_can_be_customized_for_view_routes()
        {

            $this->createRoutes(function () {

                $this->router->view('/foo', 'welcome.wordpress', [
                    'foo' => 'bar', 'bar' => 'baz',
                ], 201, ['Referer' => 'foobar']);

            });


            $request = $this->webRequest('GET', '/foo');

            $this->runAndAssertOutput('VIEW:welcome.wordpress,CONTEXT:[foo=>bar,bar=>baz]', $request);


            $this->assertContains('Referer: foobar', $this->emitter->headers);
            $this->assertStringContainsString('201', $this->emitter->status_line);

        }

        private function createBindingsForViewController()
        {

            $this->container->instance(ResponseFactory::class, $this->createResponseFactory());


        }

    }

