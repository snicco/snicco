<?php


    declare(strict_types = 1);


    namespace Tests\unit\Session;

    use Illuminate\Config\Repository;
    use Slim\Csrf\Guard;
    use Tests\helpers\AssertsResponse;
    use Tests\stubs\TestRequest;
    use Tests\unit\UnitTest;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\ArraySessionHandler;
    use WPEmerge\Session\CsrfMiddleware;
    use WPEmerge\Session\CsrfStore;
    use WPEmerge\Session\InvalidCsrfTokenException;
    use WPEmerge\Session\SessionStore;

    class CsrfMiddlewareTest extends UnitTest
    {

        use AssertsResponse;

        /**
         * @var Request
         */
        private $request;

        /**
         * @var Delegate
         */
        private $route_action;

        /**
         * @var ArraySessionHandler
         */
        private $handler;

        protected function beforeTestRun()
        {

            $response = $this->createResponseFactory();

            $this->route_action = new Delegate(function (Request $request) use ($response) {

                return $response->make();


            });

            $this->request = TestRequest::from('POST', '/foo');

            $this->handler = new ArraySessionHandler(10);
            $this->handler->write($this->sessionId(), serialize([
                'csrf' => [
                    'secret_csrf_name' => 'secret_csrf_value',
                ],
            ]));
            $this->handler->write($this->anotherSessionId(), serialize([
                'csrf' => [
                    'secret_csrf_name' => 'secret_csrf_value',
                ],
            ]));

        }

        private function sessionId() : string
        {

            return str_repeat('a', 40);

        }

        private function anotherSessionId() : string
        {

            return str_repeat('b', 40);
        }

        private function newSessionStore(string $cookie_name = 'test_session', $handler = null) : SessionStore
        {

            $handler = $handler ?? new ArraySessionHandler(10);

            return new SessionStore($cookie_name, $handler);

        }

        private function newMiddleware(SessionStore $session, string $mode = 'rotate') : CsrfMiddleware
        {

            $csrf_store = new CsrfStore($session);

            return new CsrfMiddleware(
                $f = $this->createResponseFactory(),
                new Guard(
                    $f,
                    'csrf',
                    $csrf_store,
                   null,
                    1,
                    32,
                    false,
                ),
                $mode
            );

        }

        /** @test */
        public function an_existing_csrf_token_can_be_validated()
        {

            $session = $this->newSessionStore('test_session', $this->handler);
            $session->setId($this->sessionId());
            $session->start();

            $response = $this->newMiddleware($session)->handle($this->request->withParsedBody([
                'csrf_name' => 'secret_csrf_name',
                'csrf_value' => 'secret_csrf_value',
            ]), $this->route_action);

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function a_failed_csrf_gets_throws_an_exception()
        {

            $this->expectExceptionMessage(InvalidCsrfTokenException::class);

            $session = $this->newSessionStore('test_session', $this->handler);
            $session->setId(
                $this->sessionId()
            )->start();

            $request = $this->createRequest($session, [
                'csrf_name' => 'secret_csrf_name',
                'csrf_value' => 'wrong_csrf_value',
            ]);

           $this->newMiddleware($session)->handle($request, $this->route_action);




        }

        private function createRequest(
            SessionStore $session,
            array $body = [
                'csrf_name' => 'secret_csrf_name',
                'csrf_value' => 'wrong_csrf_value',
            ]
        ) {

            return $this->request
                ->withSession($session)
                ->withParsedBody($body)
                ->withAddedHeader('X-Requested-With', 'XMLHttpRequest');


        }


    }