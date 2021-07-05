<?php


    declare(strict_types = 1);


    namespace Tests\unit\Auth;

    use Mockery as m;
    use Tests\helpers\TravelsTime;
    use Tests\MiddlewareTestCase;
    use WPMvc\Auth\AuthSessionManager;
    use WPMvc\Auth\Middleware\AuthenticateSession;
    use WPMvc\Http\Delegate;
    use WPMvc\Session\Drivers\ArraySessionDriver;
    use WPMvc\Session\Session;

    class AuthenticateSessionTest extends MiddlewareTestCase
    {

        use TravelsTime;

        /**
         * @var AuthSessionManager
         */
        private $session_manager;

        /** @var AuthenticateSession */
        private $middleware;

        protected function setUp() : void
        {

            parent::setUp();
            $this->backToPresent();
            $this->session_manager = m::mock(AuthSessionManager::class);
            $this->route_action = new Delegate(function () {
                return $this->response_factory->make(200);
            });

        }

        protected function tearDown() : void
        {
            m::close();
            parent::tearDown();
        }

        public function newMiddleware()
        {
            return $this->middleware;
        }

        /** @test */
        public function auth_confirmation_session_data_is_forgotten_for_idle_sessions () {

            $session = new Session(new ArraySessionDriver(10));
            $session->put('auth.confirm.foo', 'bar');

            $this->middleware = new AuthenticateSession($this->session_manager);

            $this->session_manager->shouldReceive('idleTimeout')->andReturn(300);

            $request = $this->request->withSession($session);

            $response = $this->runMiddleware($request);

            $this->assertFalse($session->has('auth.confirm'));
            $this->assertStatusCode(200, $response);

        }

          /** @test */
        public function auth_confirmation_session_data_is_not_forgotten_for_active_sessions () {

            $session = new Session(new ArraySessionDriver(10));
            $session->put('auth.confirm.foo', 'bar');
            $session->setLastActivity(time());

            $this->middleware = new AuthenticateSession($this->session_manager);

            $this->session_manager->shouldReceive('idleTimeout')->andReturn(300);

            $request = $this->request->withSession($session);

            $response = $this->runMiddleware($request);

            $this->assertTrue($session->has('auth.confirm'));
            $this->assertStatusCode(200, $response);
        }

        /** @test */
        public function custom_keys_can_be_removed_from_the_session_on_idle_timeout () {

            $session = new Session(new ArraySessionDriver(10));
            $session->put('auth.confirm.foo', 'bar');
            $session->put('foo.bar.baz', 'biz');
            $session->put('foo.biz.bam', 'boo');
            $session->put('biz', 'boo');

            $this->middleware = new AuthenticateSession($this->session_manager, ['foo.bar', 'biz'] );

            $this->session_manager->shouldReceive('idleTimeout')->andReturn(300);

            $request = $this->request->withSession($session);

            $response = $this->runMiddleware($request);

            $this->assertFalse($session->has('auth.confirm'));
            $this->assertFalse($session->has('biz'));
            $this->assertFalse($session->has('foo.bar'));
            $this->assertTrue($session->has('foo.biz'));
            $this->assertStatusCode(200, $response);

        }


    }