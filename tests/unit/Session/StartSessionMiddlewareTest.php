<?php


    declare(strict_types = 1);


    namespace Tests\unit\Session;

    use Illuminate\Support\Carbon;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;
    use WPEmerge\Support\VariableBag;

    class StartSessionMiddlewareTest extends UnitTest
    {

        use AssertsResponse;
        use CreateRouteCollection;
        use CreateUrlGenerator;

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
                                            'test_session' => $this->sessionId(),
                                        ]));

            $this->cookies = new Cookies();

        }

        private function newMiddleware(Session $store = null, $gc_collection = [0,100]) : StartSessionMiddleware
        {

            $store = $store ?? $this->newSessionStore();

            $config = $this->config;

            $config['lottery'] = $gc_collection;

            return new StartSessionMiddleware($store, $this->cookies , $config);

        }

        private function newSessionStore(string $cookie_name = 'test_session', $handler = null) : Session
        {

            $handler = $handler ?? new ArraySessionDriver(10);

            return new Session($cookie_name, $handler);

        }

        private function sessionId() : string
        {

            return str_repeat('a', 40);

        }

        private function anotherSessionId() : string
        {

            return str_repeat('b', 40);
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
            $handler->write($this->sessionId(), serialize(['foo' => 'bar']));
            $handler->write($this->anotherSessionId(), serialize(['foo' => 'baz']));

            $store = $this->newSessionStore('test_session', $handler);

            $response = $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $session = $this->getRequestSession($response);

            $this->assertArrayHasKey('foo', $session->all());

        }

        /** @test */
        public function a_session_without_matching_session_cookie_will_create_a_new_session()
        {

            $handler = new ArraySessionDriver(10);
            $handler->write($this->anotherSessionId(), serialize(['foo' => 'bar']));

            $store = $this->newSessionStore('test_session', $handler);

            $response = $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $session = $this->getRequestSession($response);

            $this->assertArrayNotHasKey('foo', $session->all());

        }

        /** @test */
        public function the_previous_url_is_saved_to_the_session_after_creating_the_response () {

            $handler = new ArraySessionDriver(10);

            $store = $this->newSessionStore('test_session', $handler);

            $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $persisted_url = unserialize($handler->read($this->sessionId()))['_url']['previous'];

            $this->assertSame('https://foo.com/foo', $persisted_url );


        }

        /** @test */
        public function values_added_to_the_session_are_saved () {

            $handler = new ArraySessionDriver(10);

            $store = $this->newSessionStore('test_session', $handler);

            $this->newMiddleware($store)->handle($this->request, $this->route_action);

            $persisted_data = unserialize($handler->read($this->sessionId()));

            $this->assertSame('calvin', $persisted_data['name'] );

        }

        /** @test */
        public function garbage_collection_works () {

            $handler = new ArraySessionDriver(10);

            $handler->write($this->anotherSessionId(), serialize(['foo' => 'bar']));

            $this->assertNotSame('', unserialize($handler->read($this->anotherSessionId())));


            Carbon::setTestNow(Carbon::now()->addSeconds(120));

            $handler->write($this->sessionId(), serialize(['foo' => 'bar']));
            $store = $this->newSessionStore('test_session', $handler);


            $this->newMiddleware($store, [100,100])->handle($this->request, $this->route_action);


            $this->assertSame('',  $handler->read($this->anotherSessionId()));

            $this->assertNotSame('', unserialize($handler->read($this->sessionId())));

            Carbon::setTestNow();

        }

        /** @test */
        public function the_session_cookie_is_added_to_the_response () {

            Carbon::setTestNow(Carbon::createFromTimestamp(1));

            $this->assertEmpty($this->cookies->toHeaders());

            $this->newMiddleware()->handle($this->request, $this->route_action);

            $this->assertNotEmpty($this->cookies->toHeaders());

            $cookie = $this->cookies->toHeaders()[0];

            $this->assertStringStartsWith("test_session={$this->sessionId()}",$cookie);
            $this->assertStringContainsString('path=/', $cookie);
            $this->assertStringContainsString('SameSite=Lax', $cookie);
            $this->assertStringContainsString('expires=Thu, 01-Jan-1970 00:01:01 UTC', $cookie);
            $this->assertStringContainsString('HttpOnly', $cookie);
            $this->assertStringContainsString('secure', $cookie);
            $this->assertStringNotContainsString('domain', $cookie);

            Carbon::setTestNow();

        }

    }