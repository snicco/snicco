<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Mockery;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\UnitTest;
    use Tests\stubs\TestRequest;
    use Tests\helpers\AssertsResponse;
    use BetterWP\Support\WP;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Http\ResponseFactory;
    use BetterWP\Middleware\Authenticate;
    use BetterWP\Http\Responses\RedirectResponse;

    class AuthenticateTest extends UnitTest
    {

        use AssertsResponse;
        use CreateUrlGenerator;
        use CreateRouteCollection;

        /**
         * @var Authenticate
         */
        private $middleware;

        /**
         * @var Delegate
         */
        private $route_action;

        /**
         * @var TestRequest
         */
        private $request;

        /**
         * @var ResponseFactory
         */
        private $response;


        protected function beforeTestRun()
        {

            $response = $this->createResponseFactory();
            $this->route_action = new Delegate(function () use ($response) {

                return $response->html('FOO');

            });
            $this->response = $response;
            $this->request = TestRequest::from('GET', '/foo');
            WP::shouldReceive('loginUrl')->andReturn('foobar.com')->byDefault();


        }

        private function newMiddleware(string $url = null)
        {

            return new Authenticate($this->response, $url);

        }

        protected function beforeTearDown()
        {

            WP::clearResolvedInstances();
            Mockery::close();

        }

        /** @test */
        public function logged_in_users_can_access_the_route()
        {

            WP::shouldReceive('isUserLoggedIn')->andReturnTrue();

            $response = $this->newMiddleware()->handle($this->request, $this->route_action);

            $this->assertOutput('FOO', $response);

        }

        /** @test */
        public function logged_out_users_cant_access_the_route()
        {

            WP::shouldReceive('isUserLoggedIn')->andReturnFalse();

            $response = $this->newMiddleware()->handle($this->request, $this->route_action);

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertStatusCode(302, $response);

        }

        /** @test */
        public function by_default_users_get_redirected_to_wp_login_with_the_current_url_added_to_the_query_args()
        {

            WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
            WP::shouldReceive('loginUrl')->andReturnUsing(function ($redirect_to) {

                return 'https://foo.com/login?redirect_to='.$redirect_to;

            });

            $enc = urlencode('üäö');
            $request = TestRequest::fromFullUrl('GET', 'https://foo.com/'.$enc.'?param=1');

            $response = $this->newMiddleware()->handle($request, $this->route_action);

            $expected = '/login?redirect_to=/'.$enc.'?param=1';

            $this->assertSame($expected, $response->getHeaderLine('Location'));


        }

        /** @test */
        public function users_can_be_redirected_to_a_custom_url()
        {

            WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
            WP::shouldReceive('loginUrl')->andReturnUsing(function ($redirect_to) {

                return 'https://foo.com/login?redirect_to='.$redirect_to;

            });

            $expected = '/login?redirect_to=/my-custom-login';

            $response = $this->newMiddleware('/my-custom-login')
                             ->handle($this->request, $this->route_action);

            $this->assertSame($expected, $response->getHeaderLine('Location'));

        }

        /** @test */
        public function json_responses_are_returned_for_ajax_requests()
        {

            WP::shouldReceive('isUserLoggedIn')->andReturnFalse();

            $response = $this->newMiddleware()->handle(
                $this->request->withAddedHeader('X-Requested-With', 'XMLHttpRequest')
                              ->withAddedHeader('Accept', 'application/json'),
                $this->route_action
            );

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(401, $response);
            $this->assertContentType('application/json', $response);

        }

    }
