<?php


    declare(strict_types = 1);


    namespace Tests\integration\Http;

    use Tests\integration\IntegrationTest;
    use Tests\fixtures\Middleware\GlobalMiddleware;
    use Tests\fixtures\Middleware\WebMiddleware;
    use Tests\stubs\TestRequest;

    class HttpKernelMiddlewareTest extends IntegrationTest
    {

        /** @test */
        public function custom_middleware_groups_can_be_defined () {

            $GLOBALS['test'][WebMiddleware::run_times] = 0;

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'middleware' => [
                    'groups' => [
                        'custom_group' => [
                            WebMiddleware::class
                        ]
                    ]
                ]
            ]);

            $this->seeKernelOutput('foo', TestRequest::from('GET', 'middleware/foo'));
            $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times]);

        }

        /** @test */
        public function global_middleware_is_run_when_a_route_matches () {

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'middleware' => [
                    'groups' => [
                        'global' => [
                            GlobalMiddleware::class
                        ]
                    ]
                ]
            ]);

            $this->seeKernelOutput('get_fallback', TestRequest::from('GET', 'post1'));
            $this->assertSame(
                1,
                $GLOBALS['test'][GlobalMiddleware::run_times],
                'Middleware was not run but was expected to.'
            );

        }

        /** @test */
        public function global_middleware_is_not_run_by_default_if_no_route_matches () {

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'middleware' => [
                    'groups' => [
                      'global' => [
                          GlobalMiddleware::class
                      ]
                    ]
                ]
            ]);

            // there is no put route in Routes/web.php
            $this->seeKernelOutput('', TestRequest::from('PUT', 'middleware/foo'));
            $this->assertSame(0, $GLOBALS['test'][GlobalMiddleware::run_times], 'Middleware was run unexpectedly.');
        }

        /** @test */
        public function global_middleware_can_be_enabled_to_run_always_even_without_matching_a_route () {

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'middleware' => [
                    'groups' => [
                        'global' => [
                            GlobalMiddleware::class
                        ]
                    ],
                    'always_run_global' => true,
                ]
            ]);

            // there is no put route in Routes/web.php
            $this->seeKernelOutput('', TestRequest::from('PUT', 'middleware/foo'));
            $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times], 'Middleware was not run as expected');

        }

        /** @test */
        public function global_middleware_is_not_run_twice_for_fallback_routes () {

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'middleware' => [
                    'groups' => [
                        'global' => [
                            GlobalMiddleware::class
                        ]
                    ],
                    'always_run_global' => true,
                ]
            ]);



            $this->seeKernelOutput('get_fallback', TestRequest::from('GET', 'post1'));
            $this->assertSame(
                1,
                $GLOBALS['test'][GlobalMiddleware::run_times],
                'Middleware was not run as expected.'
            );

        }

        /** @test */
        public function global_middleware_is_not_run_twice_for_matching_url_routes () {

            $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'middleware' => [
                    'groups' => [
                        'global' => [
                            GlobalMiddleware::class
                        ]
                    ],
                    'always_run_global' => true,
                ]
            ]);



            $this->seeKernelOutput('foo', TestRequest::from('GET', 'foo'));
            $this->assertSame(
                1,
                $GLOBALS['test'][GlobalMiddleware::run_times],
                'Middleware was not run as expected.'
            );

        }

    }