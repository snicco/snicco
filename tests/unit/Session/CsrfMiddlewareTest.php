<?php


    declare(strict_types = 1);


    namespace Tests\unit\Session;

    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\helpers\HashesSessionIds;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Session\Middleware\CsrfMiddleware;
    use WPEmerge\Session\CsrfStore;
    use WPEmerge\Session\GuardFactory;
    use WPEmerge\Session\Exceptions\InvalidCsrfTokenException;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;

    class CsrfMiddlewareTest extends UnitTest
    {

        use AssertsResponse;
        use CreateUrlGenerator;
        use CreateRouteCollection;
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
         * @var ArraySessionDriver
         */
        private $handler;

        protected function beforeTestRun()
        {

            WP::shouldReceive('userID')->andReturn(1)->byDefault();

            $response = $this->createResponseFactory();

            $this->route_action = new Delegate(function () use ($response) {

                return $response->make();


            });

            $this->request = TestRequest::from('POST', '/foo');

            $this->handler = new ArraySessionDriver(10);
            $this->handler->write($this->hashedSessionId(), serialize([
                'csrf' => [
                    'secret_csrf_name' => 'secret_csrf_value',
                ],
            ]));
            $this->handler->write($this->hash($this->anotherSessionId()), serialize([
                'csrf' => [
                    'secret_csrf_name' => 'secret_csrf_value',
                ],
            ]));

        }

        protected function beforeTearDown()
        {
            WP::reset();
            \Mockery::close();
        }

        private function sessionId() : string
        {

            return str_repeat('a', 64);

        }

        private function anotherSessionId() : string
        {

            return str_repeat('b', 64);
        }

        private function newSessionStore( $handler = null) : Session
        {

            $handler = $handler ?? new ArraySessionDriver(10);

            return new Session( $handler);

        }

        private function newMiddleware(Session $session, string $mode = 'rotate') : CsrfMiddleware
        {

            $csrf_store = new CsrfStore($session);

            return new CsrfMiddleware(
                GuardFactory::create($this->createResponseFactory(), $csrf_store),
                $mode
            );

        }

        /** @test */
        public function a_csrf_token_can_be_validated()
        {

            $session = $this->newSessionStore($this->handler);
            $session->start($this->getSessionId());
            $request = $this->createRequest($session);

            $response = $this->newMiddleware($session)->handle($request, $this->route_action);

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function a_failed_csrf_check_throws_an_exception()
        {

            $this->expectException(InvalidCsrfTokenException::class);

            $session = $this->newSessionStore($this->handler);
            $session->start($this->getSessionId());

            $request = $this->createRequest($session, [
                'csrf_name' => 'secret_csrf_name',
                'csrf_value' => 'wrong_csrf_value',
            ]);

            $this->newMiddleware($session)->handle($request, $this->route_action);


        }

        /** @test */
        public function a_failed_csrf_check_deletes_all_stored_tokens_for_the_session () {


            $session = $this->newSessionStore( $this->handler);
            $session->start($this->getSessionId());

            $this->assertTrue($session->has('csrf'));

            $request = $this->createRequest($session, [
                'csrf_name' => 'secret_csrf_name',
                'csrf_value' => 'wrong_csrf_value',
            ]);


            try {

                $this->newMiddleware($session)->handle($request, $this->route_action);

                $this->fail('No Csrf Exception thrown');

            }
            catch (InvalidCsrfTokenException $e) {

                $this->assertFalse($session->has('csrf'));

            }


        }

        /** @test */
        public function by_default_the_csrf_token_is_replaced_in_the_session_on_successful_validation () {

            $session = $this->newSessionStore( $this->handler);
            $session->start($this->getSessionId());
            $request = $this->createRequest($session);

            $this->assertSame('secret_csrf_value', $session->get('csrf.secret_csrf_name'));

            $this->newMiddleware($session)->handle($request, $this->route_action);

            // Old token is deleted.
            $this->assertFalse($session->has('csrf.secret_csrf_name'));

            $csrf = $session->get('csrf');

            // New token is created.
            $this->assertNotEmpty($csrf);
            $this->assertStringStartsWith('csrf', Arr::firstKey($csrf));



        }

        /** @test */
        public function the_session_token_can_be_persisted_for_different_middleware_instances () {


            $session = $this->newSessionStore( $this->handler);
            $session->start($this->sessionId());
            $request = $this->createRequest($session);

            $this->assertSame('secret_csrf_value', $session->get('csrf.secret_csrf_name'));

            $this->newMiddleware($session, 'persist')->handle($request, $this->route_action);

            $this->assertSame('secret_csrf_value', $session->get('csrf.secret_csrf_name'));


        }

        /** @test */
        public function only_one_session_token_is_saved_for_persistent_mode () {

            $session = $this->newSessionStore( $this->handler);
            $session->start($this->sessionId());
            $request = $this->createRequest($session);

            $this->newMiddleware($session, 'persist')->handle($request, $this->route_action);

            $this->assertCount(1, $session->get('csrf'));

        }

        /** @test */
        public function only_one_session_token_is_saved_for_rotating_mode () {

            $session = $this->newSessionStore( $this->handler);
            $session->start($this->sessionId());
            $request = $this->createRequest($session);

            $this->newMiddleware($session)->handle($request, $this->route_action);

            $this->assertCount(1, $session->get('csrf'));

        }

        /** @test */
        public function a_key_value_pair_can_be_created_for_the_body_when_the_active_session_has_no_csrf_token () {

            $session = $this->newSessionStore();
            $guard = GuardFactory::create($this->createResponseFactory(), new CsrfStore($session));

            $csrf_field = new CsrfField($session, $guard);
            $body = $csrf_field->create();
            $request = $this->createRequest($session, $body);

            $response = $this->newMiddleware($session)->handle($request, $this->route_action);

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function a_key_value_pair_can_be_created_if_the_session_already_has_a_token () {

            $session = $this->newSessionStore();
            $session->put('csrf', ['secret_name' => 'secret_value']);
            $guard = GuardFactory::create($this->createResponseFactory(), new CsrfStore($session));

            $csrf_field = new CsrfField($session, $guard);
            $body = $csrf_field->create();
            $request = $this->createRequest($session, $body);

            $response = $this->newMiddleware($session, 'persist')->handle($request, $this->route_action);

            $this->assertStatusCode(200, $response);

            $this->assertSame(['secret_name' => 'secret_value'], $session->get('csrf'));

        }

        /** @test */
        public function the_newly_generated_token_gets_saved_in_the_session () {

            $session = $this->newSessionStore();
            $guard = GuardFactory::create($this->createResponseFactory(), new CsrfStore($session));
            $this->assertEmpty($session->get('csrf'));

            $csrf_field = new CsrfField($session, $guard);

            $csrf_field->create();

            $this->assertNotEmpty($session->get('csrf'));

        }

        private function createRequest(
            Session $session,
            ?array $body = [
                'csrf_name' => 'secret_csrf_name',
                'csrf_value' => 'secret_csrf_value',
            ]
        ) {

            return $this->request
                ->withSession($session)
                ->withParsedBody($body)
                ->withAddedHeader('X-Requested-With', 'XMLHttpRequest');


        }


    }