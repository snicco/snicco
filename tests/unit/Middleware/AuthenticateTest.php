<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Mockery;
    use Tests\unit\UnitTest;
    use Tests\stubs\TestRequest;
    use Tests\helpers\AssertsResponse;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Authenticate;
    use WPEmerge\Http\Responses\RedirectResponse;

    class AuthenticateTest extends UnitTest
    {

        use AssertsResponse;

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

        private function newMiddleware( string $url = null ) {

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

                return 'example.com/login?redirect='.$redirect_to;

            });

            $expected = 'example.com/login?redirect='.$this->request->getFullUrl();

            $response = $this->newMiddleware()->handle($this->request, $this->route_action);

            $this->assertSame($expected, $response->getHeaderLine('Location'));


        }


		/** @test */
		public function users_can_be_redirected_to_a_custom_url()
        {

            WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
            WP::shouldReceive('loginUrl')->times(0);

            $expected = 'https://foobarbaz.com';

            $response = $this->newMiddleware('https://foobarbaz.com')
                             ->handle($this->request, $this->route_action);

            $this->assertSame($expected, $response->getHeaderLine('Location'));

        }

	}
