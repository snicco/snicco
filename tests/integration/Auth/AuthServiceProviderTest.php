<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\AuthSessionManager;
    use WPEmerge\Auth\Controllers\AuthController;
    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Auth\Middleware\AuthenticateSession;
    use WPEmerge\Auth\PasswordAuthenticator;
    use WPEmerge\Auth\WpAuthSessionToken;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Events\NewLogin;
    use WPEmerge\Session\Events\NewLogout;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;

    class AuthServiceProviderTest extends IntegrationTest
    {

        private $config = [
            'session' => [
                'enabled' => true,
                'driver' => 'array',
                'lifetime' => 3600
            ],
            'providers' => [
                SessionServiceProvider::class,
                AuthServiceProvider::class,
            ],
        ];

        /** @test */
        public function the_auth_views_are_bound_in_the_config()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    AuthServiceProvider::class,
                    SessionServiceProvider::class,
                ],
            ]);

            $views = TestApp::config('views');
            $expected = ROOT_DIR.DS.'src'.DS.'Auth'.DS.'views';

            $this->assertContains($expected, $views);


        }

        /** @test */
        public function middleware_aliases_are_bound()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    AuthServiceProvider::class,
                    SessionServiceProvider::class,
                ],
            ]);

            $middleware_aliases = TestApp::config('middleware.aliases');

            $this->assertArrayHasKey('auth.confirmed', $middleware_aliases);
            $this->assertArrayHasKey('auth.unconfirmed', $middleware_aliases);

        }

        /** @test */
        public function global_middleware_is_added()
        {

            $this->newTestApp($this->config);
            $this->assertContains(AuthenticateSession::class, TestApp::config('middleware.groups.global'));

        }

        /** @test */
        public function the_auth_routes_are_bound_in_the_config()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                    AuthServiceProvider::class,
                ],
            ]);

            $routes = TestApp::config('routing.definitions');
            $expected = ROOT_DIR.DS.'src'.DS.'Auth'.DS.'routes';

            $this->assertContains($expected, $routes);

        }

        /** @test */
        public function all_controllers_are_bound()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                    AuthServiceProvider::class,
                ],
            ]);

            $this->assertInstanceOf(ConfirmAuthMagicLinkController::class, TestApp::resolve(ConfirmAuthMagicLinkController::class));
            $this->assertInstanceOf(ForgotPasswordController::class, TestApp::resolve(ForgotPasswordController::class));
            $this->assertInstanceOf(ResetPasswordController::class, TestApp::resolve(ResetPasswordController::class));
            $this->assertInstanceOf(AuthController::class, TestApp::resolve(AuthController::class));

        }

        /** @test */
        public function the_wp_login_logout_events_from_the_session_package_are_unset()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                    AuthServiceProvider::class,
                ],
            ]);

            $listeners = TestApp::config('events.listeners');

            $login = $listeners[NewLogin::class] ?? [];
            $logout = $listeners[NewLogout::class] ?? [];

            $this->assertNotContains([SessionManager::class, 'migrateAfterLogin'], $login);
            $this->assertNotContains([SessionManager::class, 'invalidateAfterLogout'], $logout);

        }

        /** @test */
        public function the_config_is_bound()
        {

            $this->newTestApp($this->config);

            $this->assertSame(3600 * 3, TestApp::config('auth.confirmation.duration'));
            $this->assertSame(3600 * 24 * 7, TestApp::config('auth.remember.lifetime'));
            $this->assertSame(1800, TestApp::config('auth.timeouts.idle'));
            $this->assertSame('auth', TestApp::config('auth.endpoint'));
            $this->assertArrayHasKey('auth', TestApp::config('routing.api.endpoints'));
            $this->assertTrue(TestApp::config('auth.remember.enabled'));


        }

        /** @test */
        public function the_authenticator_is_bound()
        {

            $this->newTestApp($this->config);

            $this->assertInstanceOf(PasswordAuthenticator::class, TestApp::resolve(\WPEmerge\Auth\Contracts\Authenticator::class));

        }

        /** @test */
        public function the_WP_Session_Token_class_is_extended()
        {

            $this->newTestApp($this->config);

            $instance = \WP_Session_Tokens::get_instance(1);
            $this->assertInstanceOf(WpAuthSessionToken::class, $instance);

        }

        /** @test */
        public function the_session_manager_interface_is_replaced()
        {

            $this->newTestApp($this->config);

            $this->assertInstanceOf(AuthSessionManager::class, TestApp::resolve(SessionManagerInterface::class));

        }

        /** @test */
        public function testSessionLifetimeRememberEnabled()
        {

            $this->newTestApp(array_merge($this->config, [
                'auth' => [
                    'remember' => [
                        'enabled' => true,
                        'lifetime' => SessionManager::WEEK_IN_SEC,
                    ]
                ],
            ]));

            $this->assertSame(SessionManager::WEEK_IN_SEC, TestApp::config('session.lifetime'));
            $this->assertSame(SessionManager::WEEK_IN_SEC, TestApp::config('auth.remember.lifetime'));
            $this->assertSame(SessionManager::WEEK_IN_SEC, TestApp::config('auth.timeouts.absolute'));
        }

        /** @test */
        public function testSessionLifetimeRememberFalse () {

            $this->newTestApp(array_merge($this->config, [
                'auth' => [
                    'remember' => [
                        'enabled' => false,
                        'lifetime' => SessionManager::WEEK_IN_SEC,
                    ]
                ],
            ]));

            $this->assertSame(3600, TestApp::config('session.lifetime'));
            $this->assertSame(3600, TestApp::config('auth.remember.lifetime'));
            $this->assertSame(3600, TestApp::config('auth.timeouts.absolute'));

        }

    }