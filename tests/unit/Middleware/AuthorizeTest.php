<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

    use Mockery;
    use Tests\UnitTest;
	use Tests\stubs\TestRequest;
    use Tests\traits\AssertsResponse;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Authenticate;
	use WPEmerge\ExceptionHandling\Exceptions\AuthorizationException;
    use WPEmerge\Middleware\Authorize;

    class AuthorizeTest extends UnitTest {


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
         * @var ResponseFactory
         */
        private $response;


        protected function beforeTestRun()
        {

            $response = $this->createResponseFactory();
            $this->route_action = new Delegate(function () use ($response) {

                return $response->html('FOO');

            },$this->createContainer());
            $this->request = TestRequest::from('GET', '/foo');
            WP::shouldReceive('loginUrl')->andReturn('foobar.com')->byDefault();


        }

        protected function beforeTearDown()
        {

            WP::clearResolvedInstances();
            Mockery::close();

        }


		/** @test */
		public function a_user_with_given_capabilities_can_access_the_route() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'manage_options' )
			  ->andReturnTrue();

			$response = $this->newMiddleware()->handle(
				$this->request,
				$this->route_action
            );

			$this->assertOutput( 'FOO', $response );

		}

		/** @test */
		public function a_user_without_authorisation_to_the_route_will_throw_an_exception() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'manage_options' )
			  ->andReturnFalse();

			$this->expectException( AuthorizationException::class );

			$this->newMiddleware()->handle( $this->request, $this->route_action);


		}

		/** @test */
		public function the_user_can_be_authorized_against_a_resource() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post', '10' )
			  ->once()
			  ->andReturnTrue();

			$response = $this->newMiddleware('edit_post', '10' )
                             ->handle($this->request, $this->route_action,);

			$this->assertOutput( 'FOO', $response );

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post', '10' )
			  ->once()
			  ->andReturnFalse();

			$this->expectException( AuthorizationException::class );

			$this->newMiddleware('edit_post', '10' )->handle(
				$this->request,
				$this->route_action,
			);

		}

		/** @test */
		public function several_wordpress_specific_arguments_can_be_passed() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post_meta', '10', 'test_key' )
			  ->once()
			  ->andReturnTrue();

			$response = $this->newMiddleware('edit_post_meta', '10', 'test_key')
                             ->handle($this->request, $this->route_action,);

			$this->assertOutput( 'FOO', $response );

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post_meta', '10', 'test_key' )
			  ->once()
			  ->andReturnFalse();

			$this->expectException( AuthorizationException::class );

            $this->newMiddleware('edit_post_meta', '10', 'test_key')
                             ->handle($this->request, $this->route_action,);

		}


        private function newMiddleware( string $capability = 'manage_options', ...$args ) {

            return new Authorize( $capability, ...$args);

        }


    }
