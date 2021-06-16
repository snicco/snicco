<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Psr\Http\Message\ResponseInterface;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;

    class ApiRoutesTest extends IntegrationTest
    {

        /** @test */
        public function an_api_endpoint_can_be_created_where_all_routes_are_run_on_init_and_shut_down_the_script_afterwards () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                    'api' => [
                        'endpoints' => [
                            'test' => 'api-prefix/base'
                        ]
                    ]
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', 'api-prefix/base/foo'));

            ApplicationEvent::fake([ResponseSent::class]);

            ob_start();

            do_action('init');

            $this->assertSame('foo endpoint', ob_get_clean());
            HeaderStack::assertHasStatusCode(200);
            ApplicationEvent::assertDispatched(ResponseSent::class);

        }

        /** @test */
        public function routes_are_not_loaded_twice_if_the_same_name_is_present () {

            $GLOBALS['test']['api_routes'] = false;
            $GLOBALS['test']['other_api_routes'] = false;


            $this->newTestApp([
                'routing' => [
                    'definitions' => [ROUTES_DIR, FIXTURES_DIR . DS . 'OtherRoutes'],
                    'api' => [
                        'endpoints' => [
                            'test' => 'api-prefix/base'
                        ]
                    ]
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', 'api-prefix/base/foo'));

            ob_start();

            do_action('init');

            $this->assertSame('foo endpoint', ob_get_clean());
            HeaderStack::assertHasStatusCode(200);

            $this->assertTrue($GLOBALS['test']['api_routes']);
            $this->assertFalse($GLOBALS['test']['other_api_routes'], 'Route file with the same name was loaded');



        }

        /** @test */
        public function a_middleware_group_is_automatically_added () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => [ROUTES_DIR, FIXTURES_DIR . DS . 'OtherRoutes'],
                    'api' => [
                        'endpoints' => [
                            'test' => 'api-prefix/base'
                        ]
                    ]
                ],
                'middleware' => [

                    'groups' => [

                        'api.test' => [
                            TestApiMiddleware::class
                        ]

                    ],


                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', 'api-prefix/base/foo'));

            ob_start();

            do_action('init');

            $this->assertSame('you cant access this api endpoint.', ob_get_clean());
            HeaderStack::assertHasStatusCode(403);

        }

        /** @test */
        public function a_fallback_api_route_can_be_defined_that_matches_all_non_existing_endpoints () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => [ROUTES_DIR, FIXTURES_DIR . DS . 'OtherRoutes'],
                    'api' => [
                        'endpoints' => [
                            'test' => 'api-prefix/base'
                        ]
                    ]
                ],
            ]);

            $this->rebindRequest(TestRequest::from('GET', 'api-prefix/base/bogus'));

            ob_start();

            do_action('init');

            $this->assertSame('The endpoint: bogus does not exist.', ob_get_clean());
            HeaderStack::assertHasStatusCode(400);

        }

    }

    class TestApiMiddleware extends Middleware {

        /**
         * @var ResponseFactory
         */
        private $factory;

        public function __construct(ResponseFactory $factory)
        {
            $this->factory = $factory;
        }

        public function handle(Request $request, Delegate $next)
        {
            return $this->factory->make(403)->html($this->factory->createStream('you cant access this api endpoint.'));
        }

    }