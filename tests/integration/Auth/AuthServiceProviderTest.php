<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Snicco\Auth\Authenticators\MagicLinkAuthenticator;
    use Snicco\Auth\Authenticators\PasswordAuthenticator;
    use Snicco\Auth\Authenticators\RedirectIf2FaAuthenticable;
    use Snicco\Auth\Authenticators\TwoFactorAuthenticator;
    use Snicco\Auth\AuthSessionManager;
    use Snicco\Auth\Confirmation\EmailAuthConfirmation;
    use Snicco\Auth\Confirmation\TwoFactorAuthConfirmation;
    use Snicco\Auth\Contracts\AuthConfirmation;
    use Snicco\Auth\Contracts\LoginResponse;
    use Snicco\Auth\Contracts\LoginViewResponse;
    use Snicco\Auth\Controllers\AuthSessionController;
    use Snicco\Auth\Controllers\ConfirmedAuthSessionController;
    use Snicco\Auth\Controllers\ForgotPasswordController;
    use Snicco\Auth\Controllers\ResetPasswordController;
    use Snicco\Auth\Middleware\AuthenticateSession;
    use Snicco\Auth\Responses\MagicLinkLoginView;
    use Snicco\Auth\Responses\PasswordLoginView;
    use Snicco\Auth\Responses\RedirectToDashboardResponse;
    use Snicco\Auth\WpAuthSessionToken;
    use Snicco\Events\Event;
    use Snicco\Events\ResponseSent;
    use Snicco\ExceptionHandling\Exceptions\ConfigurationException;
    use Snicco\Http\Responses\RedirectResponse;
    use Snicco\Middleware\Secure;
    use Snicco\Session\Contracts\SessionManagerInterface;
    use Snicco\Session\Events\NewLogin;
    use Snicco\Session\Events\NewLogout;
    use Snicco\Session\Middleware\StartSessionMiddleware;
    use Snicco\Session\SessionManager;
    use Tests\AuthTestCase;
    use Tests\stubs\TestApp;
    use WP_Session_Tokens;

    class AuthServiceProviderTest extends AuthTestCase
    {

        protected bool $defer_boot = true;

        /** @test */
        public function the_config_is_extended()
        {

            $this->boot();

            $this->assertSame(10, TestApp::config('auth.confirmation.duration'));
            $this->assertSame(1800, TestApp::config('auth.idle'));
            $this->assertSame(false, TestApp::config('auth.features.remember_me'));
            $this->assertFalse(TestApp::config('auth.features.2fa'));
            $this->assertFalse(TestApp::config('auth.features.password-resets'));
            $this->assertFalse(TestApp::config('auth.features.registration'));


        }

        /** @test */
        public function an_exception_is_thrown_if_sessions_are_not_enabled()
        {

            $this->withReplacedConfig('session.enabled', false);

            try {

                $this->boot();
                $this->fail('No Configuration exceptions were thrown when they were expected');

            }
            catch (ConfigurationException $e) {

                $this->assertStringContainsString('Sessions need to be enabled', $e->getMessage());

            }

        }

        /** @test */
        public function the_auth_endpoints_have_defaults_set()
        {

            $this->boot();

            $this->assertSame('auth', TestApp::config('auth.endpoints.prefix'));
            $this->assertArrayHasKey('auth', TestApp::config('routing.api.endpoints'));

            $this->assertSame('login', TestApp::config('auth.endpoints.login'));
            $this->assertSame('magic-link', TestApp::config('auth.endpoints.magic-link'));
            $this->assertSame('confirm', TestApp::config('auth.endpoints.confirm'));
            $this->assertSame('two-factor', TestApp::config('auth.endpoints.2fa'));
            $this->assertSame('challenge', TestApp::config('auth.endpoints.challenge'));
            $this->assertSame('register', TestApp::config('auth.endpoints.register'));
            $this->assertSame('forgot-password', TestApp::config('auth.endpoints.forgot-password'));
            $this->assertSame('reset-password', TestApp::config('auth.endpoints.reset-password'));
            $this->assertSame('accounts', TestApp::config('auth.endpoints.accounts'));
            $this->assertSame('create', TestApp::config('auth.endpoints.accounts_create'));


        }

        /** @test */
        public function the_auth_views_are_bound_in_the_config()
        {

            $this->boot();

            $views = TestApp::config('view.paths');
            $expected = ROOT_DIR.DS.'src'.DS.'Auth'.DS.'views';

            $this->assertContains($expected, $views);
            $this->assertContains($expected.'/partials', $views);
            $this->assertContains($expected.'/email', $views);


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
        public function the_start_session_middleware_has_a_higher_priority_then_the_authenticate_session_middleware()
        {


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

            $instance = WP_Session_Tokens::get_instance(1);
            $this->assertInstanceOf(WpAuthSessionToken::class, $instance);

        }

        /** @test */
        public function the_session_manager_interface_is_replaced()
        {

            $this->boot();

            $this->assertInstanceOf(AuthSessionManager::class, TestApp::resolve(SessionManagerInterface::class));

        }

        /** @test */
        public function wp_login_php_is_a_permanent_redirected_for_all_requests_expect_personal_privacy_deletions()
        {

            $this->withoutExceptionHandling();
            $this->default_server_variables = [
                'REQUEST_METHOD' => 'GET',
                'SCRIPT_NAME' => 'wp-login.php',
            ];
            $this->withRequest($this->frontendRequest('GET', 'wp-login.php'));
            $this->boot();

            Event::fake([ResponseSent::class]);

            do_action('init');

            $response = $this->sentResponse();

            $query = urlencode('/wp-admin/index.php');
            $expected_redirect = "/auth/login?redirect_to=$query";

            $response->assertRedirect($expected_redirect)
                     ->assertStatus(301);

            Event::assertDispatched(function (ResponseSent $event) {

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

            $this->assertSame($this->config->get('session.lifetime'), $lifetime);

        }

        /** @test */
        public function the_cookie_expiration_is_synced_with_the_custom_session_lifetime()
        {

            $this->boot();

            $lifetime = apply_filters('auth_cookie_expiration', 10000);

            $this->assertSame($this->config->get('session.lifetime'), $lifetime);

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
        public function email_can_be_used_as_a_primary_authenticator()
        {

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
        public function two_factor_authentication_can_be_enabled()
        {

            $this->withAddedConfig('auth.features.2fa', true);
            $this->boot();

            $pipeline = TestApp::config('auth.through');

            $this->assertEquals([
                TwoFactorAuthenticator::class, RedirectIf2FaAuthenticable::class,
                PasswordAuthenticator::class,
            ], $pipeline);

        }

        /** @test */
        public function auth_confirmation_uses_email_by_default()
        {

            $this->boot();
            $this->assertInstanceOf(EmailAuthConfirmation::class, TestApp::resolve(AuthConfirmation::class));

        }

        /** @test */
        public function two_factor_auth_confirmation_is_used_if_enabled()
        {

            $this->withAddedConfig('auth.features.2fa', true);
            $this->boot();

            $this->assertInstanceOf(TwoFactorAuthConfirmation::class, TestApp::resolve(AuthConfirmation::class));


        }

    }