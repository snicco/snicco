<?php


    declare(strict_types = 1);


    namespace Tests\unit\Auth;

    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\TravelsTime;
    use Tests\MiddlewareTestCase;
    use WPEmerge\Auth\Middleware\ConfirmAuth;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Routing\Route;

    class ConfirmAuthTest extends MiddlewareTestCase
    {

        use CreateDefaultWpApiMocks;
        use TravelsTime;

        protected function setUp() : void
        {

            parent::setUp();
            $this->backToPresent();
            $route = new Route(['GET'], '/auth/confirm', function () {});
            $route->name('auth.confirm');
            $this->routes->add($route);
        }

        protected function tearDown() : void
        {

            parent::tearDown();
            Mockery::close();
            WP::reset();
            $this->backToPresent();


        }

        public function newMiddleware() :ConfirmAuth {

            $this->route_action = new Delegate(function ()  {
                return $this->response_factory->html('Access granted,');
            });

            return new ConfirmAuth();
        }

        /** @test */
        public function a_missing_auth_confirmed_token_does_not_grant_access_to_a_route () {

            $request = $this->request->withSession($this->newSession());

            $response = $this->runMiddleware($request);

            $this->assertStatusCode(302, $response);

        }

        /** @test */
        public function unconfirmed_users_are_redirect_to_the_correct_route () {



            $request = $this->request->withSession($this->newSession());

            $response = $this->runMiddleware($request);

            $this->assertStatusCode(302, $response);
            $this->assertHeader('Location', '/auth/confirm', $response);

        }

        /** @test */
        public function an_expired_auth_token_does_not_grant_access () {

            $request = $this->request->withSession($s = $this->newSession());

            $s->confirmAuthUntil(200);

            $this->travelIntoFuture(201);
            $response = $this->runMiddleware($request);

            $this->assertStatusCode(302, $response);

        }

        /** @test */
        public function a_valid_token_grants_access () {

            $request = $this->request->withSession($s = $this->newSession());

            $s->confirmAuthUntil(200);

            $this->travelIntoFuture(199);
            $response = $this->runMiddleware($request);

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function the_current_url_is_saved_as_intended_url_to_the_session_on_get_requests () {

            $request = $this->request->withSession($s = $this->newSession());

            $response = $this->runMiddleware($request);

            $this->assertStatusCode(302, $response);
            $this->assertSame($request->fullPath(), $s->getIntendedUrl());

        }

        /** @test */
        public function the_previous_url_is_saved_for_post_request_as_the_intended_url () {

            $request = $this->request->withSession($s = $this->newSession());

            $s->setPreviousUrl('/foo/bar');

            $response = $this->runMiddleware($request->withMethod('POST'));

            $this->assertStatusCode(302, $response);
            $this->assertSame('/foo/bar', $s->getIntendedUrl());


        }



    }