<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Session\DatabaseSessionHandler;
    use WPEmerge\Session\SessionHandler;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Session\SessionStore;

    class SessionServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function sessions_are_disabled_by_default () {

            $this->newTestApp();

            $this->assertNull(TestApp::config('session.enable'));

        }

        /** @test */
        public function sessions_can_be_enabled_in_the_config () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertTrue(TestApp::config('session.enabled'));

        }

        /** @test */
        public function the_cookie_name_has_a_default_value () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertSame('wp_mvc_session', TestApp::config('session.cookie'));

        }

        /** @test */
        public function a_cookie_name_can_be_set () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                    'cookie' => 'test_cookie'
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertSame('test_cookie', TestApp::config('session.cookie'));

        }

        /** @test */
        public function the_session_table_has_a_default_value () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertSame('sessions', TestApp::config('session.table'));

        }

        /** @test */
        public function the_default_lottery_chance_is_2_percent () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertSame([2, 100], TestApp::config('session.lottery'));


        }

        /** @test */
        public function the_session_cookie_path_is_root_by_default () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertSame('/', TestApp::config('session.path'));

        }

        /** @test */
        public function the_session_cookie_domain_is_null_by_default () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertNull( TestApp::config('session.domain', ''));

        }

        /** @test */
        public function the_session_cookie_is_set_to_only_secure () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertTrue( TestApp::config('session.secure'));

        }

        /** @test */
        public function the_session_cookie_is_set_to_http_only () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertTrue( TestApp::config('session.http_only'));

        }

        /** @test */
        public function same_site_is_set_to_lax () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertSame( 'lax', TestApp::config('session.same_site'));

        }

        /** @test */
        public function session_lifetime_is_set_to_120_minutes () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $this->assertSame( 120 , TestApp::config('session.lifetime'));

        }

        /** @test */
        public function the_session_store_can_be_resolved () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $store = TestApp::resolve(SessionStore::class);

            $this->assertInstanceOf(SessionStore::class, $store);

        }

        /** @test */
        public function the_database_driver_is_used_by_default () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ]
            ]);

            $driver = TestApp::resolve(SessionHandler::class);

            $this->assertInstanceOf(DatabaseSessionHandler::class, $driver);


        }


    }