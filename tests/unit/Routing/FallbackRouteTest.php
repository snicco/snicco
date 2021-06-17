<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\fixtures\Conditions\IsPost;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\stubs\TestViewFactory;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ViewFactoryInterface;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\View\ViewFactory;

    class FallbackRouteTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;
        use CreateUrlGenerator;
        use CreateRouteCollection;

        /** @var Router */
        private $router;

        private $container;

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

        }

        protected function beforeTearDown()
        {

            Mockery::close();
            ApplicationEvent::setInstance(null);
            WP::reset();

        }

        /** @test */
        public function for_web_request_the_fallback_route_controller_evaluates_all_routes_with_WP_conditions_and_no_url()
        {

            $this->createRoutes(function () {

                $this->router->get()->where(IsPost::class, true)
                             ->handle(function () {

                                 return 'FOO';

                             });

                $this->router->createFallbackWebRoute();

            });

            $request = $this->webRequest('GET', '/post1');
            $this->runAndAssertOutput('FOO', $request);


        }

        /** @test */
        public function routes_that_do_have_a_wordpress_condition_AND_a_url_pattern_lead_to_not_calling_the_fallback_controller()
        {

            $this->createRoutes(function () {

                $this->router->get('post2')->where(IsPost::class, true)
                             ->handle(function () {

                                 return 'FOO';
                             });

                $this->router->createFallbackWebRoute();

            });

            $request = $this->webRequest('GET', 'post1');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', 'post2');
            $this->runAndAssertOutput('FOO', $request);



        }

        /** @test */
        public function users_can_create_a_custom_fallback_route_that_gets_run_if_the_fallback_controller_could_not_resolve_any_valid_wp_condition_route()
        {

            $this->createRoutes(function () {

                $this->router->get()->where(IsPost::class, false)
                             ->handle(function () {

                                 return 'FOO';

                             });

                $this->router->createFallbackWebRoute();

            });


            $request = $this->webRequest('GET', 'post1');
            $this->runAndAssertEmptyOutput($request);

            $this->createRoutes(function () {

                $this->router->get()->where(IsPost::class, false)
                             ->handle(function () {

                                 return 'FOO';

                             });

                $this->router->createFallbackWebRoute();

                $this->router->fallback(function (Request $request) {

                    return 'FOO';

                });
            });


            $request = $this->webRequest('GET', 'post1');
            $this->runAndAssertOutput('FOO', $request);

        }



    }