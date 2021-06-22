<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\fixtures\Middleware\GlobalMiddleware;
    use Tests\fixtures\Middleware\WebMiddleware;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;

    class FallbackControllerTest extends IntegrationTest
    {


        /** @test */
        public function the_fallback_route_is_not_run_for_robots_text()
        {

            $this->newTestApp(TEST_CONFIG);

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', 'robots.txt'));

            TestApp::route()->fallback(function () {

                return 'foo_fallback';

            });

            ob_start();
            apply_filters('template_include', 'wp.php');
            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();

        }

        /** @test */
        public function the_fallback_route_is_not_run_for_sitemap_xml()
        {

            $this->newTestApp(TEST_CONFIG);

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', 'sitemap.xml'));

            TestApp::route()->fallback(function () {

                return 'foo_fallback';

            });

            ob_start();
            apply_filters('template_include', 'wp.php');
            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();

        }

        /** @test */
        public function global_middleware_is_run_if_the_fallback_controller_does_not_match_a_web_route_and_has_no_user_fallback_route()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'middleware' => [

                    'groups' => [

                        'global' => [
                            GlobalMiddleware::class,
                        ],

                    ],

                ],
            ]);

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', 'bogus'));

            ob_start();
            apply_filters('template_include', 'wp.php');
            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();

            $this->assertSame(0, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware run for non matching web route.');


        }

        /** @test */
        public function global_middleware_is_run_if_the_fallback_controller_has_a_fallback_route()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'middleware' => [

                    'groups' => [

                        'global' => [
                            GlobalMiddleware::class,
                        ],

                    ],

                ],
            ]);

            TestApp::route()->fallback(function () {
                return 'FOO_FALLBACK';
            });

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', 'bogus'));

            ob_start();
            apply_filters('template_include', 'wp.php');
            $this->assertSame('FOO_FALLBACK', ob_get_clean());
            HeaderStack::assertHasStatusCode(200 );

            $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware not run for non matching web route.');


        }

        /** @test */
        public function global_middleware_is_not_run_twice_for_fallback_routes_if_nothing_matches()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'middleware' => [

                    'groups' => [

                        'global' => [
                            GlobalMiddleware::class,
                        ],

                    ],
                    'always_run_global' => true,

                ],
            ]);

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', 'bogus'));

            ob_start();
            apply_filters('template_include', 'wp.php');
            $this->assertSame('', ob_get_clean());
            HeaderStack::assertNoStatusCodeSent();

            $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware not run for non matching web route.');

        }

        /** @test */
        public function global_middleware_is_not_run_twice_if_a_user_defined_fallback_route_exists()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'middleware' => [

                    'groups' => [

                        'global' => [
                            GlobalMiddleware::class,
                        ],

                    ],
                    'always_run_global' => true
                ],
            ]);

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            TestApp::route()->fallback(function () {
                return 'FOO_FALLBACK';
            });

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', 'bogus'));

            ob_start();
            apply_filters('template_include', 'wp.php');
            $this->assertSame('FOO_FALLBACK', ob_get_clean());
            HeaderStack::assertHasStatusCode(200);

            $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times], 'global middleware not run for non matching web route.');

        }

        /** @test */
        public function web_middleware_is_run_for_non_matching_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'middleware' => [

                    'groups' => [

                        'web' => [
                            WebMiddleware::class,
                        ],

                    ],
                ],
            ]);

            $GLOBALS['test'][WebMiddleware::run_times] = 0;

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', 'bogus'));

            ob_start();
            apply_filters('template_include', 'wp.php');
            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();

            $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times], 'web middleware not run for non matching web route.');

        }


    }