<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\helpers\HashesSessionIds;
    use Tests\helpers\TravelsTime;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\AuthSessionManager;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;

    class AuthSessionManagerTest extends IntegrationTest
    {

        use HashesSessionIds;
        use InteractsWithWordpress;
        use TravelsTime;

        private $config = [
            'session' => [
                'enabled' => true,
                'driver' => 'array',
                'lifetime' => 3600,
            ],
            'auth' => [
                'remember' => [
                    'enabled' => true,
                    'lifetime' => 3600,
                ],
                'timeouts' => [
                    'idle' => 1800
                ]
            ],
            'providers' => [
                SessionServiceProvider::class,
                AuthServiceProvider::class,
            ],
        ];

        /** @var ArraySessionDriver */
        private $driver;

        /** @var AuthSessionManager */

        private $session_manager;

        /**
         * @var TestRequest
         */
        private $request;

        protected function afterSetup()
        {

            $this->newTestApp($this->config);
            $this->driver = TestApp::resolve(SessionDriver::class);
            $this->session_manager = TestApp::resolve(SessionManagerInterface::class);
            $this->request = TestRequest::from('GET', 'foo');
        }

        /** @test */
        public function all_sessions_for_the_current_user_can_be_retrieved()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $john = $this->newAdmin();
            $this->login($john);
            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $john->ID);
            $this->session_manager->save();

            $this->login($calvin);
            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $calvin->ID);
            $sessions = $this->session_manager->getAllForUser();

            // Sessions from john are not included.
            $this->assertCount(2, $sessions);

            $this->login($john);
            $this->bindNewSessionManager();
            $this->session_manager->start($this->request, $john->ID);
            $sessions = $this->session_manager->getAllForUser();

            // Sessions from calvin are not included.
            $this->assertCount(1, $sessions);

        }

        /** @test */
        public function all_sessions_for_the_current_user_can_be_destroyed () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $sessions = $this->session_manager->getAllForUser();
            $this->assertCount(2, $sessions);

            $john = $this->newAdmin();
            $this->login($john);
            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $john->ID);
            $this->session_manager->save();

            $this->session_manager->destroyAllForUser($calvin->ID);

            // Johns session still there.
            $this->assertCount(1, $this->session_manager->getAllForUser());

            $this->bindNewSessionManager();

            $this->login($calvin);
            $this->session_manager->start($this->request, $calvin->ID);

            // Calvin's session are gone.
            $this->assertCount(0, $this->session_manager->getAllForUser());



        }

        /** @test */
        public function all_other_sessions_for_the_current_user_can_be_destroyed () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $token = $this->hash($this->activeSession()->getId());

            $this->session_manager->destroyOthersForUser($token, $calvin->ID);

            $this->assertCount(1, $this->session_manager->getAllForUser());


        }

        /** @test */
        public function all_sessions_can_be_destroyed () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $john = $this->newAdmin();
            $this->login($john);
            $this->bindNewSessionManager();

            $this->session_manager->start($this->request, $john->ID);
            $this->session_manager->save();

            $this->assertCount(3 , $this->driver->all());

            $this->session_manager->destroyAll();

            $this->assertCount(0 , $this->driver->all());



        }

        /** @test */
        public function expired_sessions_are_not_included () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            // Simulate activity on the session.
            $this->travelIntoFuture(1800);
            $this->session_manager->save();

            $this->travelIntoFuture(1800);

            $this->assertCount(1, $this->session_manager->getAllForUser());

            $this->travelIntoFuture(1);

            $this->assertCount(0, $this->session_manager->getAllForUser());


        }

        /** @test */
        public function idle_sessions_are_included_if_persistent_login_is_enabled () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $this->travelIntoFuture(1800);

            $this->assertCount(1, $this->session_manager->getAllForUser());

            $this->travelIntoFuture(1);

            // The session is idle but we dont invalidate it.
            // This happens in another middleware where session content is flushed.
            $this->assertCount(1, $this->session_manager->getAllForUser());

        }

        /** @test */
        public function idle_sessions_are_not_included_if_remember_me_is_disabled () {

            Arr::set($this->config, 'auth.remember.enabled', false);
            $this->afterSetup();

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $this->travelIntoFuture(1800);

            $this->assertCount(1, $this->session_manager->getAllForUser());

            $this->travelIntoFuture(1);

            $this->assertCount(0, $this->session_manager->getAllForUser());

        }

        /** @test */
        public function the_idle_timeout_can_be_customized_at_runtime () {

            Arr::set($this->config, 'auth.remember.enabled', false);
            $this->afterSetup();

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->session_manager->start($this->request, $calvin->ID);
            $this->session_manager->save();

            $this->session_manager->setIdleResolver(function ($idle) {

                return $idle-1;

            });

            $this->travelIntoFuture(1800);

            $this->assertCount(0, $this->session_manager->getAllForUser());


        }

        private function bindNewSessionManager()
        {

            TestApp::container()->instance(Session::class, $s = new Session(TestApp::resolve(SessionDriver::class)));

            TestApp::container()->instance(SessionManagerInterface::class,
                $m = new AuthSessionManager(
                     new SessionManager( TestApp::config('session'), $s),
                    TestApp::resolve(SessionDriver::class),
                    TestApp::config('auth')
                )
            );

            $this->session_manager = $m;

        }

        private function activeSession() : Session
        {

            return $this->session_manager->activeSession();
        }

    }