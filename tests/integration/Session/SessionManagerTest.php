<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Illuminate\Support\InteractsWithTime;
    use Tests\helpers\HashesSessionIds;
    use Tests\helpers\TravelsTime;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Events\SessionRegenerated;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;

    class SessionManagerTest extends IntegrationTest
    {


        use HashesSessionIds;
        use InteractsWithTime;
        use TravelsTime;

        /**
         * @var SessionManager
         */
        private $manager;

        /**
         * @var Session
         */
        private $session;

        /**
         * @var TestRequest
         */
        private $request;

        /**
         * @var ArraySessionDriver
         */
        private $driver;

        private $rotate = 3600;

        private $lifetime = 7200;

        protected function config () {

            return [
                'session' => [
                    'enabled' => true,
                    'driver' => 'array',
                    'rotate' => $this->rotate,
                    'lifetime' => $this->lifetime,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ];

        }

        protected function afterSetup()
        {

            $this->newTestApp($this->config());
            $this->manager = TestApp::resolve(SessionManager::class);
            $this->driver = TestApp::resolve(SessionDriver::class);
            $this->session = TestApp::session();
            $this->request = TestRequest::from('GET', 'foo');
            $this->driver->write($this->hashedSessionId(), serialize([
                'foo' => 'bar'
            ]));

        }

        /** @test */
        public function the_session_id_is_regenerated_on_a_login_event () {


            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));

            $this->rebindRequest(TestRequest::from('GET', 'foo')
                                            ->withAddedHeader('Cookie', 'wp_mvc_session='.$this->getSessionId()));

            ob_start();

            do_action('wp_login');

            $this->assertSame('', ob_get_clean());

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->getSessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is for the new id is in the handler.
            $data = unserialize($array_handler->read($this->hash($id_after_login)));
            $this->assertSame('bar', $data['foo']);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->hashedSessionId()));

        }

        /** @test */
        public function session_are_invalidated_on_logout () {

            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));

            $this->rebindRequest(TestRequest::from('GET', 'foo')
                                            ->withAddedHeader('Cookie', 'wp_mvc_session='.$this->getSessionId()));

            ob_start();

            do_action('wp_logout');

            $this->assertSame('', ob_get_clean());

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->getSessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is for the new id is not in the handler.
            $data = unserialize($array_handler->read($this->hash($id_after_login)));
            $this->assertArrayNotHasKey('foo', $data);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->hashedSessionId()));

        }

        /** @test */
        public function the_provided_user_id_is_set_on_the_session () {

            $this->assertSame(0, $this->session->userId());

            $this->manager->start(TestRequest::from('GET', 'foo'), 2);

            $this->assertSame(2, $this->session->userId());


        }

        /** @test */
        public function initial_session_rotation_is_set () {

            $request = $this->request->withHeader('Cookie', "wp_mvc_session={$this->getSessionId()}");
            $this->manager->start($request, 1);

            $this->assertSame(0, $this->session->rotationDueAt());

            $this->manager->save();

            $this->assertSame($this->availableAt($this->rotate), $this->session->rotationDueAt());

        }

         /** @test */
        public function absolute_session_timeout_is_set () {

            $request = $this->request->withHeader('Cookie', "wp_mvc_session={$this->getSessionId()}");
            $this->manager->start($request, 1);

            $this->assertSame(0, $this->session->absoluteTimeout());

            $this->manager->save();

            $this->assertSame($this->availableAt($this->lifetime), $this->session->absoluteTimeout());

        }

        /** @test */
        public function the_cookie_expiration_is_equal_to_the_max_lifetime () {

            $this->manager->start($this->request, 1);
            $this->manager->save();

            $cookie = $this->manager->sessionCookie()->properties();
            $this->assertSame( $this->availableAt($this->lifetime), $cookie['expires']);

        }

        /** @test */
        public function sessions_are_not_rotated_before_the_interval_passes () {

            $request = $this->request->withHeader('Cookie', "wp_mvc_session={$this->getSessionId()}");
            $this->manager->start($request, 1);
            $this->manager->save();
            $this->assertSame($this->availableAt($this->rotate), $this->session->rotationDueAt());

            $this->travelIntoFuture(3599);
            $this->manager->save();
            $this->backToPresent();

            $this->assertSame($this->availableAt($this->rotate), $this->session->rotationDueAt());
            $this->assertSame($this->session->getId(), $this->getSessionId());

            $this->travelIntoFuture(3601);
            $this->manager->save();
            $this->backToPresent();

            $this->assertNotSame($this->session->getId(), $this->getSessionId());

        }

        /** @test */
        public function the_regenerate_session_event_gets_dispatched () {

            ApplicationEvent::fake([SessionRegenerated::class]);

            $request = $this->request->withHeader('Cookie', "wp_mvc_session={$this->getSessionId()}");
            $this->manager->start($request, 1);
            $this->manager->save();

            $this->travelIntoFuture(3601);
            $this->manager->save();
            $this->backToPresent();

            ApplicationEvent::assertDispatched(function (SessionRegenerated $event)  {

                return $event->session === $this->session;

            });

        }

    }