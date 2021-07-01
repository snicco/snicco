<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\AuthTestCase;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Auth\Authenticators\MagicLinkAuthenticator;
    use WPEmerge\Auth\Authenticators\PasswordAuthenticator;
    use WPEmerge\Auth\Authenticators\RedirectIf2FaAuthenticable;
    use WPEmerge\Auth\Authenticators\TwoFactorAuthenticator;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\AuthSessionManager;
    use WPEmerge\Auth\Confirmation\EmailAuthConfirmation;
    use WPEmerge\Auth\Confirmation\TwoFactorAuthConfirmation;
    use WPEmerge\Auth\Contracts\AuthConfirmation;
    use WPEmerge\Auth\Controllers\AuthSessionController;
    use WPEmerge\Auth\Controllers\ConfirmedAuthSessionController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Auth\Middleware\AuthenticateSession;
    use WPEmerge\Auth\Contracts\LoginResponse;
    use WPEmerge\Auth\Contracts\LoginViewResponse;
    use WPEmerge\Auth\Responses\MagicLinkLoginView;
    use WPEmerge\Auth\Responses\PasswordLoginView;
    use WPEmerge\Auth\Responses\RedirectToDashboardResponse;
    use WPEmerge\Auth\WpAuthSessionToken;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Middleware\Secure;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Events\NewLogin;
    use WPEmerge\Session\Events\NewLogout;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;

    class AuthServiceProviderTest extends AuthTestCase
    {

        protected $defer_boot = true;

        /** @test */
        public function the_config_is_bound()
        {

            $this->boot();

            $this->assertSame(3600 * 3, TestApp::config('auth.confirmation.duration'));
            $this->assertSame(1800, TestApp::config('auth.idle'));
            $this->assertSame('auth', TestApp::config('auth.endpoint'));
            $this->assertArrayHasKey('auth', TestApp::config('routing.api.endpoints'));
            $this->assertSame(0, TestApp::config('auth.features.remember_me'));
            $this->assertFalse(TestApp::config('auth.features.2fa'));
            $this->assertFalse(TestApp::config('auth.features.password-resets'));
            $this->assertFalse(TestApp::config('auth.features.registration'));


        }

        /** @test */
        public function the_auth_views_are_bound_in_the_config()
        {

            $this->boot();

            $views = TestApp::config('view.paths');
            $expected = ROOT_DIR.DS.'src'.DS.'Auth'.DS.'views';

            $this->assertContains($expected, $views);


        }

        /** @test */
        public function middleware_aliases_are_bound()
        {

            $this->boot();

            $middleware_aliases = TestApp::config('middleware.aliases');

            $this->assertArrayHasKey('auth.confirmed', $middleware_aliases);
            $this->assertArrayHasKey('auth.unconfirmed', $middleware_aliases);

        }

        /** @test */
        public function global_middleware_is_added()
        {

            $this->boot();

            $this->assertContains(AuthenticateSession::class, TestApp::config('middleware.groups.global'));
            $this->assertContains(Secure::class, TestApp::config('middleware.groups.global'));
            $this->assertContains(StartSessionMiddleware::class, TestApp::config('middleware.groups.global'));


        }

        /** @test */
        public function the_start_session_middleware_has_a_higher_priority_then_the_authenticate_session_middleware () {


            $this->boot();
            $priority = TestApp::config('middleware.priority');

            $secure = array_search(Secure::class, $priority);
            $start_session = array_search(StartSessionMiddleware::class, $priority);
            $authenticate = array_search(AuthenticateSession::class, $priority);

            $this->assertTrue($secure < $start_session && $start_session < $authenticate);

        }

        /** @test */
        public function the_auth_routes_are_bound_in_the_config()
        {

            $this->boot();

            $routes = TestApp::config('routing.definitions');
            $expected = ROOT_DIR.DS.'src'.DS.'Auth'.DS.'routes';

            $this->assertContains($expected, $routes);

        }

        /** @test */
        public function all_controllers_are_bound()
        {

            $this->boot();

            $this->assertInstanceOf(ForgotPasswordController::class, TestApp::resolve(ForgotPasswordController::class));
            $this->assertInstanceOf(ResetPasswordController::class, TestApp::resolve(ResetPasswordController::class));
            $this->assertInstanceOf(AuthSessionController::class, TestApp::resolve(AuthSessionController::class));
            $this->assertInstanceOf(ConfirmedAuthSessionController::class, TestApp::resolve(ConfirmedAuthSessionController::class));

        }

        /** @test */
        public function the_wp_login_logout_events_from_the_session_package_are_unset()
        {

            $this->boot();

            $listeners = TestApp::config('events.listeners');

            $login = $listeners[NewLogin::class] ?? [];
            $logout = $listeners[NewLogout::class] ?? [];

            $this->assertNotContains([SessionManager::class, 'migrateAfterLogin'], $login);
            $this->assertNotContains([SessionManager::class, 'invalidateAfterLogout'], $logout);

        }

        /** @test */
        public function the_authenticator_is_bound()
        {

            $this->boot();

            $this->assertInstanceOf(PasswordAuthenticator::class, TestApp::resolve(PasswordAuthenticator::class));

        }

        /** @test */
        public function the_WP_Session_Token_class_is_extended()
        {

            $this->boot();

            $instance = \WP_Session_Tokens::get_instance(1);
            $this->assertInstanceOf(WpAuthSessionToken::class, $instance);

        }

        /** @test */
        public function the_session_manager_interface_is_replaced()
        {

            $this->boot();

            $this->assertInstanceOf(AuthSessionManager::class, TestApp::resolve(SessionManagerInterface::class));

        }

        /** @test */
        public function testSessionLifetimeRememberEnabled()
        {

            $this->withAddedConfig('auth.features.remember_me', SessionManager::DAY_IN_SEC)
                 ->boot();

            $this->assertSame(SessionManager::DAY_IN_SEC, TestApp::config('session.lifetime'));
            $this->assertSame(SessionManager::DAY_IN_SEC, TestApp::config('auth.features.remember_me'));
            $this->assertSame(SessionManager::DAY_IN_SEC, TestApp::config('auth.timeouts.absolute'));

        }

        /** @test */
        public function testSessionLifetimeRememberFalse()
        {

            $this->withAddedConfig('session.lifetime', 4800)
                 ->boot();

            $this->assertSame(4800, TestApp::config('session.lifetime'));
            $this->assertSame(0, TestApp::config('auth.features.remember_me'));


        }


        /** @test */
        public function wp_login_php_is_a_permanent_redirected_for_get_requests()
        {

            $this->withoutExceptionHandling();
            $this->boot();

            ApplicationEvent::fake([ResponseSent::class]);

            $response = $this->get('/wp-login.php');

            $query = urlencode($this->config->get('app.url').'/wp-admin/');
            $expected_redirect = "/auth/login?redirect_to=$query";

            $response->assertRedirect($expected_redirect)
                     ->assertStatus(301);

            ApplicationEvent::assertDispatched(function (ResponseSent $event) {

                return $event->response instanceof RedirectResponse;

            });


        }

        /** @test */
        public function the_login_url_is_filtered()
        {

            $this->boot();
            $this->loadRoutes();

            $url = wp_login_url();

            $this->assertStringNotContainsString('wp-login', $url);
            $this->assertStringContainsString('/auth/login', $url);

        }

        /** @test */
        public function the_logout_url_is_filtered()
        {

            $this->boot();
            $this->loadRoutes();

            $url = wp_logout_url();

            $this->assertStringNotContainsString('wp-login', $url);
            $this->assertStringContainsString('/auth/logout', $url);

        }

        /** @test */
        public function the_auth_cookie_is_filtered_and_contains_the_current_session_id()
        {

            $this->boot();

            $calvin = $this->createAdmin();

            // create random id. This will be overwritten immediately.
            $this->session->setId('bogus');

            $cookie = wp_generate_auth_cookie($calvin->ID, 3600, 'auth', $token = wp_generate_password());
            $elements = wp_parse_auth_cookie($cookie);

            $this->assertNotSame($elements['token'], $token);
            $this->assertSame($elements['token'], TestApp::session()->getId());

        }

        /** @test */
        public function the_cookie_expiration_is_set_to_now_when_remember_me_is_disabled()
        {

            $this->boot();

            $lifetime = apply_filters('auth_cookie_expiration', 3600);

            $this->assertSame(0, $lifetime);

        }

        /** @test */
        public function the_cookie_expiration_is_synced_with_the_custom_session_lifetime()
        {

            $this->withAddedConfig('auth.features.remember_me', 10800);
            $this->boot();

            $lifetime = apply_filters('auth_cookie_expiration', 3600);

            $this->assertSame(10800, $lifetime);

        }

        /** @test */
        public function by_default_the_password_login_view_response_is_used()
        {

            $this->boot();

            $this->assertInstanceOf(PasswordLoginView::class, TestApp::resolve(LoginViewResponse::class));

        }

        /** @test */
        public function the_login_response_is_bound()
        {

            $this->boot();

            $this->assertInstanceOf(RedirectToDashboardResponse::class, TestApp::resolve(LoginResponse::class));
        }

        /** @test */
        public function the_login_view_response_is_bound()
        {

            $this->boot();

            $this->assertInstanceOf(PasswordLoginView::class, TestApp::resolve(LoginViewResponse::class));

        }

        /** @test */
        public function the_login_view_response_can_be_swapped_for_an_email_login_screen()
        {

            $this->withAddedConfig('auth.primary_view', MagicLinkLoginView::class);
            $this->boot();

            $this->assertInstanceOf(MagicLinkLoginView::class, TestApp::resolve(LoginViewResponse::class));

        }

        /** @test */
        public function the_auth_pipeline_uses_the_password_authenticator_by_default()
        {

            $this->boot();

            $pipeline = TestApp::config('auth.through');

            $this->assertEquals([PasswordAuthenticator::class], $pipeline);

        }

        /** @test */
        public function email_can_be_used_as_a_primary_authenticator () {

            $this->withAddedConfig('auth.authenticator', 'email');
            $this->boot();

            $pipeline = TestApp::config('auth.through');

            $this->assertEquals([MagicLinkAuthenticator::class], $pipeline);

        }

        /** @test */
        public function a_custom_auth_pipeline_can_be_used()
        {

            $this->withAddedConfig('auth.through', ['foo', 'bar']);

            $this->boot();

            $pipeline = TestApp::config('auth.through');

            $this->assertSame(['foo', 'bar'], $pipeline);

        }

        /** @test */
        public function password_resets_are_disabled_by_default()
        {

            $this->boot();

            $this->assertFalse(TestApp::config('auth.features.password-resets'));

        }

        /** @test */
        public function two_factor_authentication_can_be_enabled () {

            $this->withAddedConfig('auth.features.2fa', true);
            $this->boot();

            $pipeline = TestApp::config('auth.through');

            $this->assertEquals([TwoFactorAuthenticator::class, RedirectIf2FaAuthenticable::class, PasswordAuthenticator::class], $pipeline);

        }

        /** @test */
        public function auth_confirmation_uses_email_by_default () {

            $this->boot();
            $this->assertInstanceOf(EmailAuthConfirmation::class, TestApp::resolve(AuthConfirmation::class));

        }

        /** @test */
        public function two_factor_auth_confirmation_is_used_if_enabled () {

            $this->withAddedConfig('auth.features.2fa', true);
            $this->boot();

            $this->assertInstanceOf(TwoFactorAuthConfirmation::class, TestApp::resolve(AuthConfirmation::class));


        }

    }