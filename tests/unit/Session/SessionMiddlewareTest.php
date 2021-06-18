<?php


    declare(strict_types = 1);


    namespace Tests\unit\Session;

    use Illuminate\Support\Carbon;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\helpers\HashesSessionIds;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\ExceptionHandling\TestingErrorHandler;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Core\ShareCookies;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\Middleware\SessionMiddleware;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Support\VariableBag;

    class SessionMiddlewareTest extends UnitTest
    {

        use AssertsResponse;
        use CreateRouteCollection;
        use CreateUrlGenerator;
        use HashesSessionIds;

        /**
         * @var Request
         */
        private $request;

        /**
         * @var Delegate
         */
        private $route_action;

        /**
         * @var Cookies
         */
        private $cookies;

        private $config = [
            'lifetime' => 1,
            'lottery' => [0,100],
            'cookie' => 'test_session',
            'domain' => null,
            'same_site' => 'lax',
            'http_only' => true,
            'secure' => true,
            'path' => '/'
        ];

        protected function beforeTestRun()
        {

            $response = $this->createResponseFactory();

            $this->route_action = new Delegate(function (Request $request) use ($response) {

                $response = $response->make();
                $response->request = $request;

                $session = $request->getAttribute('session');
                $session->put('name', 'calvin');

                return $response;


            });

            $this->request = TestRequest::from('GET', '/foo')
                                        ->withAttribute('cookies', new VariableBag([
                                            'test_session' => $this->getSessionId(),
                                        ]));

        }

        private function newMiddleware(Session $session = null, $gc_collection = [0,100]) : SessionMiddleware
        {

            $session = $session ?? $this->newSession();

            $config = $this->config;

            $config['lottery'] = $gc_collection;

            return new SessionMiddleware(new SessionManager($config, $session) );

        }

        private function newSession( $handler = null) : Session
        {

            $handler = $handler ?? new ArraySessionDriver(10);

            return new Session($handler);

        }

        private function getRequestSession($response) : Session
        {

            return $response->request->getAttribute('session');

        }

        /** @test */
        public function the_request_has_access_to_the_session()
        {

            $response = $this->newMiddleware()->handle($this->request, $this->route_action);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertInstanceOf(Session::class, $this->getRequestSession($response));

        }

        /** @test */
        public function the_correct_session_gets_created_from_the_cookie()
        {

            $handler = new ArraySessionDriver(10);
            $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
            $handler->write($this->anotherSessionId(), serialize(['foo' => 'baz']));

            $store = $this->newSession( $handler);

            $response = $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $session = $this->getRequestSession($response);

            $this->assertSame('bar', $session->get('foo'));

        }

        /** @test */
        public function a_session_without_matching_session_cookie_in_the_driver_will_create_a_new_session()
        {

            $handler = new ArraySessionDriver(10);
            $handler->write($this->hash($this->anotherSessionId()), serialize(['foo' => 'bar']));

            $store = $this->newSession( $handler);

            $response = $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $session = $this->getRequestSession($response);

            $this->assertArrayNotHasKey('foo', $session->all());

        }

        /** @test */
        public function the_previous_url_is_saved_to_the_session_after_creating_the_response () {

            $handler = new ArraySessionDriver(10);
            $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']) );

            $store = $this->newSession( $handler);

            $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $persisted_url = unserialize($handler->read($this->hashedSessionId()))['_url']['previous'];

            $this->assertSame('https://foo.com/foo', $persisted_url );


        }

        /** @test */
        public function values_added_to_the_session_are_saved () {

            $handler = new ArraySessionDriver(10);
            $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar'] ) );

            $store = $this->newSession( $handler);

            $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $persisted_data = unserialize($handler->read($this->hashedSessionId()));

            $this->assertSame('calvin', $persisted_data['name'] );

        }

        /** @test */
        public function garbage_collection_works () {

            $handler = new ArraySessionDriver(10);
            $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
            $this->assertNotSame('', unserialize($handler->read($this->hashedSessionId())));


            Carbon::setTestNow(Carbon::now()->addSeconds(120));

            $store = $this->newSession($handler);

            $this->newMiddleware($store, [100,100])->handle($this->request, $this->route_action);

            $this->assertSame('',  $handler->read($this->hashedSessionId()));


            Carbon::setTestNow();

        }

        /** @test */
        public function the_session_cookie_is_added_to_the_response () {

            Carbon::setTestNow(Carbon::createFromTimestamp(1));

            $c = $this->createContainer();
            $response_factory = $this->createResponseFactory();
            $c->instance(ResponseFactory::class, $response_factory);

            $pipeline = new Pipeline($c, new TestingErrorHandler());

            $request = TestRequest::from('GET', 'foo');

            $session = $this->newSession();

            $response = $pipeline
                ->send($request)
                ->through([ShareCookies::class, $this->newMiddleware($session)])
                ->then(function () use ($response_factory) {

                    return $response_factory->make();

                });


            $cookies = $response->getHeaderLine('Set-Cookie');

            $this->assertStringStartsWith("test_session={$session->getId()}",$cookies);
            $this->assertStringContainsString('path=/', $cookies);
            $this->assertStringContainsString('SameSite=Lax', $cookies);
            $this->assertStringContainsString('expires=Thu, 01-Jan-1970 00:01:01 UTC', $cookies);
            $this->assertStringContainsString('HttpOnly', $cookies);
            $this->assertStringContainsString('secure', $cookies);
            $this->assertStringNotContainsString('domain', $cookies);

            Carbon::setTestNow();

        }

        /** @test */
        public function providing_a_cookie_that_does_not_have_an_active_session_regenerates_the_id () {

            // This works because the session driver has an active session for the the provided cookie value.
            $driver = new ArraySessionDriver(10);
            $driver->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
            $session = $this->newSession( $driver );

            $this->newMiddleware($session)->handle($this->request, $this->route_action);

            $this->assertSame('bar', $session->get('foo'));
            $this->assertSame($session->getId(), $this->getSessionId());

            // Now we reject the session id.
            $driver->destroy($this->hashedSessionId());
            $session = $this->newSession( $driver );
            $this->newMiddleware($session)->handle($this->request, $this->route_action);

            $this->assertNotSame($session->getId(), $this->getSessionId());


        }

    }