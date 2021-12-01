<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Authenticators;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Auth\Fail2Ban\TestSysLogger;
use Tests\Auth\integration\AuthTestCase;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Auth\Responses\MagicLinkLoginView;
use Snicco\Auth\Events\FailedMagicLinkAuthentication;
use Snicco\Auth\Authenticators\MagicLinkAuthenticator;

class MagicLinkAuthenticatorTest extends AuthTestCase
{
    
    private UrlGenerator $url;
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withReplacedConfig('auth.through', [
                MagicLinkAuthenticator::class,
            ]);
            $this->withReplacedConfig('auth.authenticator', 'email');
            $this->withReplacedConfig('auth.primary_view', MagicLinkLoginView::class);
            $this->withReplacedConfig('auth.fail2ban.enabled', true);
        });
        
        $this->afterApplicationBooted(function () {
            $this->url = $this->app->resolve(UrlGenerator::class);
        });
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function an_invalid_magic_link_will_fail()
    {
        $this->withoutExceptionHandling();
        
        $calvin = $this->createAdmin();
        
        $url = $this->routeUrl($calvin->ID);
        
        $response = $this->get($url.'a');
        
        $this->assertGuest();
        $response->assertRedirectPath('/auth/login');
    }
    
    /** @test */
    public function a_magic_link_for_a_non_resolvable_user_will_fail()
    {
        $calvin = $this->createAdmin();
        
        $url = $this->routeUrl($calvin->ID + 1000);
        
        $response = $this->get($url);
        
        $this->assertGuest();
        $response->assertRedirectPath('/auth/login');
    }
    
    /** @test */
    public function a_valid_link_will_log_the_user_in()
    {
        $this->withAddedConfig('auth.features.remember_me', true);
        
        $calvin = $this->createAdmin();
        $url = $this->routeUrl($calvin->ID);
        
        $this->assertNotAuthenticated($calvin);
        
        $response = $this->get($url);
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
        $this->assertTrue($this->session->hasRememberMeToken());
        $this->assertSame(
            [],
            $this->app->resolve(MagicLink::class)
                      ->getStored(),
            'Auth magic link not deleted.'
        );
    }
    
    /** @test */
    public function the_intended_url_from_the_query_string_param_of_the_magic_link_is_saved_to_the_session()
    {
        $calvin = $this->createAdmin();
        
        // We would submit the redirect value as an urlencoded form parameter.
        // This means that in the controller where we create the magic link the value would be url decoded again.
        $url = $this->routeUrl($calvin->ID, '/foo/bar?baz=foo bar');
        
        $response = $this->get($url);
        
        $response->assertLocation('/foo/bar?baz='.rawurlencode('foo bar'));
    }
    
    /** @test */
    public function failed_attempts_will_dispatch_and_event_and_redirect_to_login()
    {
        $this->followingRedirects();
        $calvin = $this->createAdmin();
        
        $url = $this->routeUrl($calvin->ID);
        
        $response = $this->get($url.'a');
        
        $response->assertSee(
            'Your magic link is either invalid or expired. Please request a new one.'
        );
        $this->assertGuest();
        $this->dispatcher->assertDispatched(
            function (FailedMagicLinkAuthentication $event) use ($calvin) {
                return $event->userId() === $calvin->ID;
            }
        );
    }
    
    /** @test */
    public function failed_attempts_will_be_recorded_by_fail2ban()
    {
        $this->default_attributes = ['ip_address' => '127.0.0.1'];
        $this->swap(Syslogger::class, $syslogger = new TestSysLogger());
        $this->followingRedirects();
        $calvin = $this->createAdmin();
        
        $url = $this->routeUrl($calvin->ID);
        
        $response = $this->get($url.'a');
        
        $response->assertSee(
            'Your magic link is either invalid or expired. Please request a new one.'
        );
        $this->assertGuest();
        
        $syslogger->assertLogEntry(
            LOG_WARNING,
            "Failed authentication with magic link for user [$calvin->ID] from 127.0.0.1"
        );
    }
    
    /** @test */
    public function post_requests_are_forwarded_to_the_next_authenticator()
    {
        $this->withAddedConfig('auth.through', [AlwaysTrueAuthenticator::class]);
        $this->withoutMiddleware('csrf');
        
        $calvin = $this->createAdmin();
        
        $response = $this->post('/auth/login', ['user_id' => $calvin->ID]);
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
    }
    
    /** @test */
    public function get_requests_without_signature_are_forwarded()
    {
        $this->withAddedConfig('auth.through', [AlwaysTrueAuthenticator::class]);
        $this->withoutMiddleware('csrf');
        
        $calvin = $this->createAdmin();
        
        $response = $this->get("/auth/login/magic-link?user_id=$calvin->ID");
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
    }
    
    private function routeUrl(int $user_id, string $redirect_to = null) :string
    {
        return $this->url->signedRoute(
            'auth.login.magic-link',
            ['query' => array_filter(['user_id' => $user_id, 'redirect_to' => $redirect_to])],
            300,
            true
        );
    }
    
}

class AlwaysTrueAuthenticator extends Authenticator
{
    
    public function attempt(Request $request, $next)
    {
        return $this->login(get_user_by('id', $request->input('user_id')));
    }
    
}