<?php


    declare(strict_types = 1);


    namespace Tests\unit\Auth;

    use Tests\MiddlewareTestCase;
    use Snicco\Auth\Middleware\AuthUnconfirmed;
    use Snicco\Support\WP;
    use Snicco\Http\Delegate;
    use Snicco\Routing\Route;
    use Snicco\Session\Drivers\ArraySessionDriver;
    use Snicco\Session\Session;

    class AuthUnconfirmedTest extends MiddlewareTestCase
    {


        protected function setUp() : void
        {

            parent::setUp();
            WP::shouldReceive('wpAdminFolder')->andReturn('wp-admin');

            $this->route_action = new Delegate(function () {

                return $this->response_factory->make(200);
            });
            $route = new Route(['GET'], '/dashboard', function () {
            });
            $route->name('dashboard');
            $this->routes->add($route);

        }

        protected function tearDown() : void
        {

            \Mockery::close();
            WP::reset();
            parent::tearDown();

        }

        public function newMiddleware() : AuthUnconfirmed
        {

            return new AuthUnconfirmed($this->generator);
        }

        /** @test */
        public function unconfirmed_session_can_bypass_the_middleware()
        {

            $session = new Session(new ArraySessionDriver(10));
            $request = $this->request->withSession($session);

            $response = $this->runMiddleware($request);

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function confirmed_sessions_are_redirected_back()
        {

            $session = new Session(new ArraySessionDriver(10));
            $session->confirmAuthUntil(300);
            $request = $this->request->withSession($session)
                                     ->withHeader('referer', 'https://foobar.com/foo/bar');

            $this->generator->setRequestResolver(function () use ($request) {

                return $request;
            });

            $response = $this->runMiddleware($request);

            $this->assertStatusCode(302, $response);
            $this->assertHeader('Location', '/foo/bar', $response);

        }

        /** @test */
        public function without_referer_header_sessions_are_redirected_to_the_dashboard()
        {

            $session = new Session(new ArraySessionDriver(10));
            $session->confirmAuthUntil(300);
            $request = $this->request->withSession($session);

            $this->generator->setRequestResolver(function () use ($request) {

                return $request;
            });

            $response = $this->runMiddleware($request);

            $this->assertStatusCode(302, $response);
            $this->assertHeader('Location', '/dashboard', $response);

        }

    }