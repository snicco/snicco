<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\CreateWpTestUrls;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use Tests\traits\SetUpRouter;
    use Tests\stubs\Conditions\ConditionWithDependency;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;

    class RouteConditionsDependencyInjectionTest extends UnitTest
    {

        use TestHelpers;
        use CreateWpTestUrls;
        use CreateDefaultWpApiMocks;

        private $router;

        private $container;

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

            Mockery::close();
            ApplicationEvent::setInstance(null);
            WP::reset();

        }

        /** @test */
        public function a_condition_gets_dependencies_injected_after_the_passed_arguments()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'foo';

                })->where(ConditionWithDependency::class, true);

            });


            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request );

        }

    }

