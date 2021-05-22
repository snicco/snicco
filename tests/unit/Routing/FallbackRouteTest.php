<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\stubs\Conditions\IsPost;
    use Tests\stubs\TestRequest;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Router;

    class FallbackRouteTest extends UnitTest
    {

        use TestHelpers;
        use CreateDefaultWpApiMocks;

        /** @var Router */
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