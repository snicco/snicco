<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Auth\Authenticators\PasswordAuthenticator;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\AuthSessionManager;
    use WPEmerge\Auth\Controllers\AuthSessionController;
    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Auth\Middleware\AuthenticateSession;
    use WPEmerge\Auth\Responses\LoginResponse;
    use WPEmerge\Auth\Responses\LoginViewResponse;
    use WPEmerge\Auth\Responses\PasswordLoginView;
    use WPEmerge\Auth\Responses\RedirectToDashboardResponse;
    use WPEmerge\Auth\WpAuthSessionToken;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Events\NewLogin;
    use WPEmerge\Session\Events\NewLogout;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;

    class AuthServiceProviderTest extends IntegrationTest
    {

        use InteractsWithWordpress;

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
            $this->assertInstanceOf(AuthSessionController::class, TestApp::resolve(AuthSessionController::class));

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

            $this->assertInstanceOf(PasswordAuthenticator::class, TestApp::resolve(PasswordAuthenticator::class));

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

        /** @test */
        public function wp_login_php_is_redirected () {

            $this->newTestApp($this->config);

            $this->rebindRequest(TestRequest::from('GET', 'foo')->withLoadingScript('wp-login.php'));

            ApplicationEvent::fake([ResponseSent::class]);

            do_action('init');

            HeaderStack::assertHasStatusCode(301);
            HeaderStack::assertContains('Location', '/auth/login');
            ApplicationEvent::assertDispatched(function (ResponseSent $event) {

                return $event->response instanceof RedirectResponse;

            });

        }

        /** @test */
        public function the_login_url_is_filtered () {

            $this->newTestApp($this->config);

            do_action('init');

            $url = wp_login_url();

            $this->assertStringNotContainsString('wp-login', $url);
            $this->assertStringContainsString('/auth/login', $url);

        }

        /** @test */
        public function the_logout_url_is_filtered () {

            $this->newTestApp($this->config);

            do_action('init');

            $url = wp_logout_url();

            $this->assertStringNotContainsString('wp-login', $url);
            $this->assertStringContainsString('/auth/logout', $url);

        }

        /** @test */
        public function the_auth_cookie_is_filtered_and_contains_the_current_session_id () {

            $this->newTestApp($this->config);

            $calvin = $this->newAdmin();

            do_action('init');
            // create random id.
            TestApp::session()->setId('bogus');

            $cookie = wp_generate_auth_cookie($calvin->ID, 3600, 'auth', $token = wp_generate_password());
            $elements = wp_parse_auth_cookie($cookie);

            $this->assertNotSame($elements['token'], $token);
            $this->assertSame($elements['token'], TestApp::session()->getId());
        }

        /** @test */
        public function the_cookie_expiration_is_filtered () {

            $this->newTestApp(array_merge($this->config, [
                'auth' => [
                    'remember' => [
                        'enabled' => true,
                        'lifetime' => SessionManager::WEEK_IN_SEC,
                    ]
                ],
            ]));

            $lifetime = apply_filters('auth_cookie_expiration', 3600);

            $this->assertSame($lifetime, SessionManager::WEEK_IN_SEC);

        }

        /** @test */
        public function by_default_the_password_login_view_response_is_used () {

            $this->newTestApp($this->config);

            $this->assertInstanceOf(PasswordLoginView::class, TestApp::resolve(LoginViewResponse::class));

        }

        /** @test */
        public function the_login_response_is_bound () {

            $this->newTestApp($this->config);

            $this->assertInstanceOf(RedirectToDashboardResponse::class, TestApp::resolve(LoginResponse::class));
        }

        /** @test */
        public function the_auth_pipeline_uses_the_password_authenticator_by_default () {

            $this->newTestApp($this->config);

            $pipeline = TestApp::config('auth.through');

            $this->assertSame([PasswordAuthenticator::class], $pipeline);

        }

        /** @test */
        public function a_custom_auth_pipeline_can_be_used () {

            Arr::set($this->config, 'auth.through', ['foo', 'bar']);

            $this->newTestApp($this->config);

            $pipeline = TestApp::config('auth.through');

            $this->assertSame(['foo', 'bar'], $pipeline);

        }

        /** @test */
        public function password_resets_are_enabled_by_default () {

            $this->newTestApp($this->config);

            $this->assertTrue(AUTH_ALLOW_PW_RESETS);

        }

        /** @test */
        public function password_resets_can_be_disabled () {


            Arr::set($this->config, 'auth.features.password-resets', false );

            $this->newTestApp($this->config);

            $this->assertFalse(AUTH_ALLOW_PW_RESETS);

        }

    }