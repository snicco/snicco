<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\HeaderStack;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\stubs\TestViewFactory;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\View\ViewFactory;

    class ViewRoutesTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;
        use CreateUrlGenerator;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            $this->container->instance(UrlGenerator::class, $this->newUrlGenerator());
            $this->container->instance(ViewFactory::class, new TestViewFactory());
            $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);
            HeaderStack::reset();
            $this->createBindingsForViewController();


        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();
            HeaderStack::reset();


        }

        /** @test */
        public function view_routes_work()
        {

            $this->createRoutes(function () {

                $this->router->view('/foo', 'welcome.wordpress');


            });

            $request = $this->webRequest('GET', '/foo');

            $this->runAndAssertOutput('VIEW:welcome.wordpress,CONTEXT:[]', $request);

            HeaderStack::assertHas('Content-Type', 'text/html');
            HeaderStack::assertHasStatusCode(200);


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

            HeaderStack::assertHas('Referer', 'foobar');
            HeaderStack::assertHasStatusCode(201);

        }

        private function createBindingsForViewController()
        {

            $this->container->instance(HttpResponseFactory::class, $this->createResponseFactory());


        }

    }

