<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\fixtures\Middleware\GlobalMiddleware;
    use Tests\fixtures\Middleware\WebMiddleware;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use WPEmerge\Routing\Router;

    class FallbackControllerTest extends TestCase
    {


        /**
         * @var Router
         */
        private $router;

        protected function setUp() : void
        {
            $this->defer_boot = true;
            $this->afterApplicationCreated(function () {
                $this->router = $this->app->resolve(Router::class);
            });

            parent::setUp();

        }

        /** @test */
        public function the_fallback_route_is_not_run_for_robots_text()
        {

            $this->boot();
            $this->router->fallback(function () {
                return 'foo_fallback';
            });
            $response = $this->get('robots.txt');
            $response->assertNullResponse();

        }

        /** @test */
        public function the_fallback_route_is_not_run_for_sitemap_xml()
        {
            $this->boot();
            $this->router->fallback(function () {
                return 'foo_fallback';
            });
            $response = $this->get('robots.txt');
            $response->assertNullResponse();

        }

        /** @test */
        public function global_middleware_is_not_run_if_the_fallback_controller_does_not_match_a_web_route_and_has_no_user_provided_fallback_route()
        {


            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
            $this->withAddedConfig(['middleware.groups.global' => [GlobalMiddleware::class]]);

            $this->get('/bogus')->assertNullResponse();

            $this->assertSame(0, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware run for non matching web route.');



        }

        /** @test */
        public function global_middleware_is_run_if_the_fallback_controller_has_a_fallback_route()
        {
            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
            $this->withAddedConfig(['middleware.groups.global' => [GlobalMiddleware::class]])->boot();

           $this->router->fallback(function () {
                return 'FOO_FALLBACK';
            });

            $this->get('/bogus')->assertOk()->assertSee('FOO_FALLBACK');

            $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware not run for non matching web route.');


        }

        /** @test */
        public function global_middleware_is_not_run_twice_for_fallback_routes_if_nothing_matches()
        {

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->withAddedConfig([
                'middleware.groups.global' => [GlobalMiddleware::class],
                'middleware.always_run_global' => true,
            ]);

            $this->get('bogus')->assertNullResponse();

            $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware not run for non matching web route.');

        }

        /** @test */
        public function global_middleware_is_not_run_twice_if_a_user_defined_fallback_route_exists()
        {

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->withAddedConfig([
                'middleware.groups.global' => [GlobalMiddleware::class],
                'middleware.always_run_global' => true,
            ])->boot();

            $this->router->fallback(function () {
                return 'FOO_FALLBACK';
            });

            $this->get('bogus')->assertOk()->assertSee('FOO_FALLBACK');

            $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware not run for non matching web route.');


        }

        /** @test */
        public function web_middleware_is_run_for_non_matching_routes_if_middleware_is_run_globally()
        {

            $GLOBALS['test'][WebMiddleware::run_times] = 0;

            $this->withAddedConfig([
                'middleware.groups.web' => [WebMiddleware::class],
                'middleware.always_run_global' => true,
            ])->boot();

            $this->get('/bogus')->assertNullResponse();

            $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times], 'web middleware not run when it was expected.');

        }

        /** @test */
        public function web_middleware_is_not_run_for_non_matching_routes_when_middleware_is_not_run_globally () {

            $GLOBALS['test'][WebMiddleware::run_times] = 0;

            $this->withAddedConfig([
                'middleware.groups.web' => [WebMiddleware::class],
                'middleware.always_run_global' => false,
            ])->boot();

            $this->get('/bogus')->assertNullResponse();

            $this->assertSame(0, $GLOBALS['test'][WebMiddleware::run_times], 'web middleware run when it was not expected.');

        }


    }