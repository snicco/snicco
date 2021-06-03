<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\unit\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\Router;

    class RedirectRoutesTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;

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
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function a_redirect_route_can_be_created () {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('HEAD', '/foo');
            $this->runAndAssertOutput('foo', $request);

        }

    }