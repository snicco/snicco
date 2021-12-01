<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Controllers;

use WP_User;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Testing\TestResponse;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Events\UserWasLoggedIn;
use Tests\Auth\integration\AuthTestCase;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Auth\Authenticators\PasswordAuthenticator;

class AuthSessionControllerTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function the_login_screen_can_be_rendered()
    {
        $this->get('/auth/login')->assertOk()
             ->assertSeeHtml('Login')
             ->assertSeeHtml('Password');
    }
    
    /** @test */
    public function the_login_route_can_not_be_accessed_while_logged_in()
    {
        $this->actingAs($this->createAdmin());
        
        $this->get('/auth/login')->assertRedirectToRoute('dashboard');
    }
    
    /** @test */
    public function reauth_works_when_present_in_the_query_parameter()
    {
        $this->withDataInSession(['foo' => 'bar']);
        
        $auth_cookies_cleared = false;
        add_action('clear_auth_cookie', function () use (&$auth_cookies_cleared) {
            $auth_cookies_cleared = true;
        });
        
        $response = $this->get('/auth/login?reauth=1');
        $response->assertOk()->assertSee('Login');
        $this->assertTrue($auth_cookies_cleared);
        $response->assertSessionMissing('foo');
    }
    
    /** @test */
    public function interim_login_is_saved_to_the_session()
    {
        $response = $this->get('/auth/login?interim-login=1');
        $response->assertOk();
        
        $response->assertSessionHas('is_interim_login', true);
    }
    
    /** @test */
    public function a_user_can_log_in()
    {
        $calvin = $this->createAdmin();
        $this->assertNotAuthenticated($calvin);
        
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
        ]);
        
        $response->assertRedirectToRoute('dashboard', 302);
        $this->assertAuthenticated($calvin);
    }
    
    /** @test */
    public function the_intended_url_is_set_in_the_session_from_the_referer_query_string()
    {
        $referer = wp_login_url('/foo/bar?baz=foo bar');
        
        $calvin = $this->createAdmin();
        
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
        ], [
            'referer' => $referer,
        ]);
        
        $response->assertLocation('/foo/bar?baz='.rawurlencode('foo bar'));
    }
    
    /** @test */
    public function auth_routes_are_run_AFTER_the_init_hook()
    {
        $this->default_server_variables = [
            'REQUEST_METHOD' => 'POST',
            'SCRIPT_NAME' => 'index.php',
        ];
        $this->withRequest($this->frontendRequest('POST', '/auth/login'));
        
        $hook_run = false;
        add_action('wp_login', function () use (&$hook_run) {
            $hook_run = true;
        });
        
        do_action('init');
        $this->assertNoResponse();
        
        $calvin = $this->createAdmin();
        $this->assertNotAuthenticated($calvin);
        
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
        ]);
        
        $response->assertRedirectToRoute('dashboard', 302);
        $this->assertAuthenticated($calvin);
        
        $this->assertTrue($hook_run);
    }
    
    /** @test */
    public function login_events_are_dispatched_correctly()
    {
        $calvin = $this->createAdmin();
        $this->assertNotAuthenticated($calvin);
        
        $login_fired = false;
        add_action('wp_login', function ($user, $id) use (&$login_fired, $calvin) {
            $login_fired = true;
            $this->assertSame($user->ID, $calvin->ID);
            $this->assertSame($id, $calvin->ID);
        }, 10, 2);
        
        $auth_cookies_sent = false;
        add_action('set_auth_cookie', function () use (&$auth_cookies_sent) {
            $auth_cookies_sent = true;
        }, 10, 5);
        
        $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
        ]);
        
        $this->assertTrue($login_fired);
        $this->assertTrue($auth_cookies_sent, 'The WP auth cookies were not sent.');
    }
    
    /** @test */
    public function the_user_is_redirect_back_to_login_if_no_authenticator_can_authenticate_the_user()
    {
        $calvin = $this->createAdmin();
        $this->assertNotAuthenticated($calvin);
        
        $this->followingRedirects();
        
        $response = $this->postToLogin([
            'pwd' => 'wrong_password',
            'log' => $calvin->user_login,
        ]);
        
        $response->assertSee('Either your username or password is not correct.');
        
        $this->assertGuest();
    }
    
    /** @test */
    public function json_requests_fail_with_422_for_failed_login_attempts()
    {
        $calvin = $this->createAdmin();
        $this->assertNotAuthenticated($calvin);
        
        $this->withHeaders(['accept' => 'application/json']);
        
        $response = $this->postToLogin([
            'pwd' => 'wrong_password',
            'log' => $calvin->user_login,
        ]);
        
        $response->assertExactJson(['message' => 'Invalid credentials.'])->assertStatus(422);
        
        $this->assertGuest();
    }
    
    /** @test */
    public function authenticators_can_return_custom_failure_responses()
    {
        $calvin = $this->createAdmin();
        $this->withReplacedConfig('auth.through', [
            CustomResponseAuthenticator::class,
        ]);
        
        $this->assertNotAuthenticated($calvin);
        
        $response = $this->postToLogin([
            'pwd' => 'wrong_password',
            'log' => $calvin->user_login,
        ]);
        
        $response->assertRedirect('/foo');
        
        $this->assertGuest();
    }
    
    /** @test */
    public function the_session_is_updated_on_login()
    {
        $this->dispatcher->fake(UserWasLoggedIn::class);
        $calvin = $this->createAdmin();
        $this->withDataInSession(['foo' => 'bar']);
        $session_id_pre_login = $this->session->getId();
        
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
        ]);
        
        // Session regenerated
        $this->assertNotSame($session_id_pre_login, $this->session->getId());
        $response->assertSessionHas(['foo' => 'bar']);
        
        // Auth confirmation set
        $this->travelIntoFuture(9);
        $this->assertTrue($this->session->hasValidAuthConfirmToken());
        $this->travelIntoFuture(1);
        $this->assertFalse($this->session->hasValidAuthConfirmToken());
        
        // User id
        $this->assertSessionUserId($calvin->ID);
        
        // remember me preference
        $this->assertFalse($this->session->hasRememberMeToken());
        
        // Login event
        $this->dispatcher->assertDispatched(function (UserWasLoggedIn $login) use ($calvin) {
            return $login->user->ID === $calvin->ID && $login->remember === false;
        });
    }
    
    /** @test */
    public function a_user_can_be_remembered_if_he_chooses_too()
    {
        $this->withAddedConfig('auth.features.remember_me', true);
        $calvin = $this->createAdmin();
        
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
            'remember_me' => '1',
        ]);
        
        $this->assertTrue($this->session->hasRememberMeToken());
    }
    
    /** @test */
    public function a_user_will_not_be_remembered_if_not_enabled_in_the_config()
    {
        $calvin = $this->createAdmin();
        
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
            'remember_me' => '1',
        ]);
        
        $this->assertFalse($this->session->hasRememberMeToken());
    }
    
    /** @test */
    public function if_its_an_interim_login_the_user_is_not_redirected_and_interim_login_is_clear_from_the_session()
    {
        $calvin = $this->createAdmin();
        
        $this->withDataInSession(['is_interim_login' => true]);
        
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
        ]);
        
        $response->assertSeeHtml(
            "jQuery(parent.document).find('.wp-auth-check-close').click();"
        )->assertOk();
        
        $response->assertSessionMissing('is_interim_login');
    }
    
    /** @test */
    public function the_user_can_be_logged_in_through_multiple_authenticators()
    {
        $calvin = $this->createAdmin();
        $this->withReplacedConfig('auth.through', [
            CustomAuthenticator::class,
            PasswordAuthenticator::class,
        ]);
        
        // Authenticate by custom authenticator
        $this->postToLogin([
            'pwd' => 'bogus',
            'log' => $calvin->user_login,
            'allow_login_for_id' => $calvin->ID,
        ]);
        
        $this->assertAuthenticated($calvin);
        
        $this->logout($calvin);
        $this->assertNotAuthenticated($calvin);
        
        // Auth will fail for both authenticators
        $response = $this->postToLogin([
            'pwd' => 'bogus',
            'log' => $calvin->user_login,
            'allow_login_for_id' => $calvin->ID + 1,
        ]);
        
        $response->assertSessionHasErrors();
        $this->assertNotAuthenticated($calvin);
        
        // Auth will be ok for second authenticator
        $response = $this->postToLogin([
            'pwd' => 'password',
            'log' => $calvin->user_login,
            'allow_login_for_id' => $calvin->ID + 1,
        ]);
        
        $this->assertAuthenticated($calvin);
        $response->assertSessionDoesntHaveErrors();
        
        $this->logout($calvin);
        $this->assertNotAuthenticated($calvin);
    }
    
    private function postToLogin(array $data, array $headers = []) :TestResponse
    {
        $token = $this->withCsrfToken();
        
        return $this->post('/auth/login', $token + $data, $headers);
    }
    
}

class CustomAuthenticator extends Authenticator
{
    
    use ResolvesUser;
    
    public function attempt(Request $request, $next) :Response
    {
        if ($request->has('allow_login_for_id')) {
            $user = $this->getUserById($request->input('allow_login_for_id'));
            
            if ($user instanceof WP_User) {
                return $this->login($user, false);
            }
        }
        
        return $next($request);
    }
    
}

class CustomResponseAuthenticator extends Authenticator
{
    
    public function attempt(Request $request, $next)
    {
        return $this->response_factory->redirect()->to('/foo');
    }
    
}