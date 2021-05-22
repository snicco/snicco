<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

	use Mockery;
    use Tests\UnitTest;
	use Tests\stubs\TestRequest;
    use Tests\traits\AssertsResponse;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Middleware\Authenticate;
	use WPEmerge\Http\RedirectResponse;
    use WPEmerge\Middleware\RedirectIfAuthenticated;

    class RedirectIfAuthenticatedTest extends UnitTest {

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
         * @var \Tests\stubs\TestRequest
         */
        private $request;

        /**
         * @var HttpResponseFactory
         */
        private $response;


        protected function beforeTestRun()
        {

            $response = $this->createResponseFactory();
            $this->route_action = new Delegate(function () use ($response) {

                return $response->html('FOO');

            }, $this->createContainer());
            $this->response = $response;
            $this->request = TestRequest::from('GET', '/foo');
            WP::shouldReceive('homeUrl')->andReturn('https://foobar.com')->byDefault();


        }

        protected function beforeTearDown()
        {

            WP::clearResolvedInstances();
            Mockery::close();

        }

        private function newMiddleware( string $redirect_url = null) {

            return new RedirectIfAuthenticated($this->response, $redirect_url);

        }

		/** @test */
		public function guests_can_access_the_route() {

			WP::shouldReceive('isUserLoggedIn')->andReturnFalse();

			$response = $this->newMiddleware()->handle( $this->request, $this->route_action );

			$this->assertOutput( 'FOO', $response );

		}


		/** @test */
		public function logged_in_users_are_redirected_to_the_home_url() {

			WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
			WP::shouldReceive('homeUrl')
			  ->with('', 'https')
			  ->andReturn(SITE_URL);

			$response = $this->newMiddleware()->handle( $this->request, $this->route_action );

			$this->assertInstanceOf( RedirectResponse::class, $response );
			$this->assertStatusCode( 302, $response );
			$this->assertSame( SITE_URL, $response->getHeaderLine( 'Location' ) );

		}

		/** @test */
		public function logged_in_users_can_be_redirected_to_custom_urls() {

			WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
			WP::shouldReceive('homeUrl')
			  ->with('', 'https')
			  ->andReturn('https://example.com/');

            $response = $this->newMiddleware('https://example.com/')
                             ->handle( $this->request, $this->route_action );

			$this->assertInstanceOf( RedirectResponse::class, $response );
			$this->assertSame( 'https://example.com/', $response->getHeaderLine( 'Location' ) );
		}

	}
