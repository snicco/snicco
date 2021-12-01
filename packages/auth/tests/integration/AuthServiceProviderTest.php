<?php

declare(strict_types=1);

namespace Tests\Auth\integration;

use WP_Session_Tokens;
use Snicco\Middleware\Secure;
use Snicco\Session\SessionManager;
use Snicco\Auth\Fail2Ban\Fail2Ban;
use Snicco\Auth\AuthSessionManager;
use Snicco\Auth\WpAuthSessionToken;
use Snicco\Session\Events\NewLogin;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Session\Events\NewLogout;
use Snicco\Auth\Fail2Ban\PHPSyslogger;
use Snicco\Auth\Responses\LoginRedirect;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\Auth\Contracts\AbstractLoginView;
use Snicco\Auth\Responses\PasswordLoginView;
use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Auth\Responses\MagicLinkLoginView;
use Snicco\Auth\Middleware\AuthenticateSession;
use Snicco\EventDispatcher\Events\ResponseSent;
use Snicco\Auth\Contracts\AbstractLoginResponse;
use Snicco\Auth\Controllers\AuthSessionController;
use Snicco\Auth\Confirmation\EmailAuthConfirmation;
use Snicco\Auth\Controllers\ResetPasswordController;
use Snicco\Auth\Authenticators\PasswordAuthenticator;
use Snicco\Auth\Controllers\ForgotPasswordController;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\Middleware\StartSessionMiddleware;
use Snicco\Auth\Authenticators\MagicLinkAuthenticator;
use Snicco\Auth\Authenticators\TwoFactorAuthenticator;
use Snicco\Auth\Confirmation\TwoFactorAuthConfirmation;
use Snicco\Auth\Authenticators\RedirectIf2FaAuthenticable;
use Snicco\Auth\Controllers\ConfirmedAuthSessionController;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class AuthServiceProviderTest extends AuthTestCase
{
    
    /** @test */
    public function the_config_is_extended()
    {
        $this->bootApp();
        
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
            $this->bootApp();
            $this->fail('No Configuration exceptions were thrown when they were expected');
        } catch (ConfigurationException $e) {
            $this->assertStringContainsString('Sessions need to be enabled', $e->getMessage());
        }
    }
    
    /** @test */
    public function the_auth_endpoints_have_defaults_set()
    {
        $this->bootApp();
        
        $this->assertSame('auth', TestApp::config('auth.endpoints.prefix'));
        
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
        $this->bootApp();
        
        $views = TestApp::config('view.paths');
        $expected = PACKAGES_DIR.DS.'auth'.DS.'views';
        
        $this->assertContains($expected, $views);
    }
    
    /** @test */
    public function middleware_aliases_are_bound()
    {
        $this->bootApp();
        
        $middleware_aliases = TestApp::config('middleware.aliases');
        
        $this->assertArrayHasKey('auth.confirmed', $middleware_aliases);
        $this->assertArrayHasKey('auth.unconfirmed', $middleware_aliases);
        $this->assertArrayHasKey('2fa.disabled', $middleware_aliases);
        $this->assertArrayHasKey('2fa.enabled', $middleware_aliases);
    }
    
    /** @test */
    public function global_middleware_is_added()
    {
        $this->bootApp();
        
        $this->assertContains(
            AuthenticateSession::class,
            TestApp::config('middleware.groups.global')
        );
        $this->assertContains(Secure::class, TestApp::config('middleware.groups.global'));
        $this->assertContains(
            StartSessionMiddleware::class,
            TestApp::config('middleware.groups.global')
        );
    }
    
    /** @test */
    public function the_start_session_middleware_has_a_higher_priority_then_the_authenticate_session_middleware()
    {
        $this->bootApp();
        $priority = TestApp::config('middleware.priority');
        
        $secure = array_search(Secure::class, $priority);
        $start_session = array_search(StartSessionMiddleware::class, $priority);
        $authenticate = array_search(AuthenticateSession::class, $priority);
        
        $this->assertTrue($secure < $start_session && $start_session < $authenticate);
    }
    
    /** @test */
    public function the_auth_routes_are_bound_in_the_config()
    {
        $this->bootApp();
        
        $routes = TestApp::config('routing.definitions');
        $expected = PACKAGES_DIR.DS.'auth'.DS.'routes';
        
        $this->assertContains($expected, $routes);
    }
    
    /** @test */
    public function all_controllers_are_bound()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            ForgotPasswordController::class,
            TestApp::resolve(ForgotPasswordController::class)
        );
        $this->assertInstanceOf(
            ResetPasswordController::class,
            TestApp::resolve(ResetPasswordController::class)
        );
        $this->assertInstanceOf(
            AuthSessionController::class,
            TestApp::resolve(AuthSessionController::class)
        );
        $this->assertInstanceOf(
            ConfirmedAuthSessionController::class,
            TestApp::resolve(ConfirmedAuthSessionController::class)
        );
    }
    
    /** @test */
    public function the_wp_login_logout_events_from_the_session_package_are_unset()
    {
        $this->bootApp();
        
        $listeners = TestApp::config('events.listeners');
        
        $login = $listeners[NewLogin::class] ?? [];
        $logout = $listeners[NewLogout::class] ?? [];
        
        $this->assertNotContains([SessionManager::class, 'migrateAfterLogin'], $login);
        $this->assertNotContains([SessionManager::class, 'invalidateAfterLogout'], $logout);
    }
    
    /** @test */
    public function the_authenticator_is_bound()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            PasswordAuthenticator::class,
            TestApp::resolve(PasswordAuthenticator::class)
        );
    }
    
    /** @test */
    public function the_WP_Session_Token_class_is_extended()
    {
        $this->bootApp();
        
        $instance = WP_Session_Tokens::get_instance(1);
        $this->assertInstanceOf(WpAuthSessionToken::class, $instance);
    }
    
    /** @test */
    public function the_session_manager_interface_is_replaced()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            AuthSessionManager::class,
            TestApp::resolve(SessionManagerInterface::class)
        );
    }
    
    /** @test */
    public function wp_login_php_is_a_permanent_redirected_for_all_requests_expect_personal_privacy_deletions()
    {
        $this->default_server_variables = [
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => 'wp-login.php',
        ];
        $this->withRequest($this->frontendRequest('GET', 'wp-login.php'));
        $this->bootApp();
        
        $this->dispatcher->fake(ResponseSent::class);
        
        do_action('init');
        
        $response = $this->sentResponse();
        
        $query = urlencode('/wp-admin/index.php');
        $expected_redirect = "/auth/login?redirect_to=$query";
        
        $response->assertRedirect($expected_redirect)
                 ->assertStatus(301);
        
        $this->dispatcher->assertDispatched(function (ResponseSent $event) {
            return $event->response instanceof RedirectResponse;
        });
    }
    
    /** @test */
    public function wp_login_php_is_not_redirected_for_personal_privacy_deletion()
    {
        $this->withServerVariables(['SCRIPT_NAME' => 'wp-login.php']);
        $this->withRequest($this->frontendRequest('GET', '/wp-login.php?action=confirmation'));
        $this->bootApp();
        
        $this->dispatcher->fake(ResponseSent::class);
        
        do_action('init');
        
        $response = $this->sentResponse();
        
        $response->assertDelegatedToWordPress();
        
        $this->dispatcher->assertNotDispatched(ResponseSent::class);
    }
    
    /** @test */
    public function the_login_url_is_filtered()
    {
        $this->bootApp();
        
        $url = wp_login_url();
        
        $this->assertStringNotContainsString('wp-login', $url);
        $this->assertStringContainsString('/auth/login', $url);
    }
    
    /** @test */
    public function the_logout_url_is_filtered()
    {
        $this->bootApp();
        
        $url = wp_logout_url();
        
        $this->assertStringNotContainsString('wp-login', $url);
        $this->assertStringContainsString('/auth/logout', $url);
    }
    
    /** @test */
    public function the_auth_cookie_is_filtered_and_contains_the_current_session_id()
    {
        $this->bootApp();
        
        $calvin = $this->createAdmin();
        
        // create random id. This will be overwritten immediately.
        $this->session->setId('bogus');
        
        $cookie =
            wp_generate_auth_cookie($calvin->ID, 3600, 'auth', $token = wp_generate_password());
        $elements = wp_parse_auth_cookie($cookie);
        
        $this->assertNotSame($elements['token'], $token);
        $this->assertSame($elements['token'], TestApp::session()->getId());
    }
    
    /** @test */
    public function the_cookie_expiration_is_set_to_now_when_remember_me_is_disabled()
    {
        $this->bootApp();
        
        $lifetime = apply_filters('auth_cookie_expiration', 3600);
        
        $this->assertSame($this->config->get('session.lifetime'), $lifetime);
    }
    
    /** @test */
    public function the_cookie_expiration_is_synced_with_the_custom_session_lifetime()
    {
        $this->bootApp();
        
        $lifetime = apply_filters('auth_cookie_expiration', 10000);
        
        $this->assertSame($this->config->get('session.lifetime'), $lifetime);
    }
    
    /** @test */
    public function by_default_the_password_login_view_response_is_used()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            PasswordLoginView::class,
            TestApp::resolve(AbstractLoginView::class)
        );
    }
    
    /** @test */
    public function the_login_response_is_bound()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            LoginRedirect::class,
            TestApp::resolve(AbstractLoginResponse::class)
        );
    }
    
    /** @test */
    public function the_login_view_response_is_bound()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            PasswordLoginView::class,
            TestApp::resolve(AbstractLoginView::class)
        );
    }
    
    /** @test */
    public function the_login_view_response_can_be_swapped_for_an_email_login_screen()
    {
        $this->withAddedConfig('auth.primary_view', MagicLinkLoginView::class);
        $this->bootApp();
        
        $this->assertInstanceOf(
            MagicLinkLoginView::class,
            TestApp::resolve(AbstractLoginView::class)
        );
    }
    
    /** @test */
    public function the_auth_pipeline_uses_the_password_authenticator_by_default()
    {
        $this->bootApp();
        
        $pipeline = TestApp::config('auth.through');
        
        $this->assertEquals([PasswordAuthenticator::class], $pipeline);
    }
    
    /** @test */
    public function email_can_be_used_as_a_primary_authenticator()
    {
        $this->withAddedConfig('auth.authenticator', 'email');
        $this->bootApp();
        
        $pipeline = TestApp::config('auth.through');
        
        $this->assertEquals([MagicLinkAuthenticator::class], $pipeline);
    }
    
    /** @test */
    public function a_custom_auth_pipeline_can_be_used()
    {
        $this->withAddedConfig('auth.through', ['foo', 'bar']);
        
        $this->bootApp();
        
        $pipeline = TestApp::config('auth.through');
        
        $this->assertSame(['foo', 'bar'], $pipeline);
    }
    
    /** @test */
    public function password_resets_are_disabled_by_default()
    {
        $this->bootApp();
        
        $this->assertFalse(TestApp::config('auth.features.password-resets'));
    }
    
    /** @test */
    public function two_factor_authentication_can_be_enabled()
    {
        $this->withAddedConfig('auth.features.2fa', true);
        $this->bootApp();
        
        $pipeline = TestApp::config('auth.through');
        
        $this->assertEquals([
            TwoFactorAuthenticator::class,
            RedirectIf2FaAuthenticable::class,
            PasswordAuthenticator::class,
        ], $pipeline);
    }
    
    /** @test */
    public function auth_confirmation_uses_email_by_default()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            EmailAuthConfirmation::class,
            TestApp::resolve(AuthConfirmation::class)
        );
    }
    
    /** @test */
    public function two_factor_auth_confirmation_is_used_if_enabled()
    {
        $this->withAddedConfig('auth.features.2fa', true);
        $this->bootApp();
        
        $this->assertInstanceOf(
            TwoFactorAuthConfirmation::class,
            TestApp::resolve(AuthConfirmation::class)
        );
    }
    
    /**
     * FAIL2BAN
     */
    
    /** @test */
    public function fail2ban_is_disabled_by_default()
    {
        $this->withOutConfig('auth.fail2ban.enabled')->bootApp();
        $this->assertNull(TestApp::config('auth.fail2ban.enabled'));
    }
    
    /** @test */
    public function the_daemon_is_set()
    {
        $this->withAddedConfig('auth.fail2ban.enabled', true);
        $this->bootApp();
        $this->assertSame('sniccowp', TestApp::config('auth.fail2ban.daemon'));
    }
    
    /** @test */
    public function the_default_facility_is_set_to_LOG_AUTH()
    {
        $this->withAddedConfig('auth.fail2ban.enabled', true);
        $this->bootApp();
        $this->assertSame(LOG_AUTH, TestApp::config('auth.fail2ban.facility'));
    }
    
    /** @test */
    public function the_default_flags_are_set()
    {
        $this->withAddedConfig('auth.fail2ban.enabled', true);
        $this->bootApp();
        $this->assertSame(LOG_NDELAY | LOG_PID, TestApp::config('auth.fail2ban.flags'));
    }
    
    /** @test */
    public function the_fail2ban_class_is_bound()
    {
        $this->withAddedConfig('auth.fail2ban.enabled', true);
        $this->bootApp();
        $this->assertInstanceOf(
            Fail2Ban::class,
            $this->app->container()->make(Fail2Ban::class)
        );
    }
    
    /** @test */
    public function the_php_syslog_is_used_by_default()
    {
        $this->withAddedConfig('auth.fail2ban.enabled', true);
        $this->bootApp();
        $this->assertInstanceOf(PHPSyslogger::class, TestApp::resolve(Syslogger::class));
    }
    
}