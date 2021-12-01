<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Controllers;

use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Mail\Testing\TestableEmail;
use Snicco\Auth\Fail2Ban\TestSysLogger;
use Tests\Auth\integration\AuthTestCase;
use Snicco\Auth\Mail\MagicLinkLoginMail;
use Snicco\Auth\Events\FailedLoginLinkCreationRequest;

class LoginMagicLinkControllerTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withReplacedConfig('auth.authenticator', 'email');
            $this->withAddedConfig('auth.fail2ban.enabled', true);
        });
        
        $this->afterApplicationBooted(function () {
            $this->withoutMiddleware('csrf');
        });
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function the_route_cant_be_accessed_if_the_authenticator_is_not_email()
    {
        $this->withReplacedConfig('auth.authenticator', 'password');
        
        $response = $this->post('/auth/login/create-magic-link');
        
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_route_cant_be_accessed_if_already_authenticated()
    {
        $this->actingAs($this->createAdmin());
        
        $response = $this->post('/auth/login/magic-link')->assertRedirectToRoute('dashboard');
    }
    
    /** @test */
    public function no_email_is_sent_for_invalid_user_login()
    {
        $response = $this->post('/auth/login/magic-link', ['login' => 'bogus']);
        $response->assertRedirect('/auth/login');
        $response->assertSessionHas('login.link.processed');
        
        $this->fake_mailer->assertNotSent(MagicLinkLoginMail::class);
    }
    
    /** @test */
    public function an_event_is_dispatched_for_invalid_logins()
    {
        $this->dispatcher->fake(FailedLoginLinkCreationRequest::class);
        
        $this->post('/auth/login/magic-link', ['login' => 'bogus']);
        
        $this->dispatcher->assertDispatched(
            fn(FailedLoginLinkCreationRequest $event) => $event->login() === 'bogus'
        );
    }
    
    /** @test */
    public function invalid_attempts_to_generate_a_link_are_recorded_with_fail2ban()
    {
        $this->swap(Syslogger::class, $logger = new TestSysLogger());
        
        $this->fromLocalhost();
        $this->post('/auth/login/magic-link', ['login' => 'bogus']);
        
        $logger->assertLogEntry(
            LOG_WARNING,
            "User enumeration trying to request a new magic link for user login [bogus] from 127.0.0.1"
        );
    }
    
    /** @test */
    public function a_login_email_is_sent_for_valid_user_login()
    {
        $calvin = $this->createAdmin();
        
        $response =
            $this->post(
                '/auth/login/magic-link',
                [
                    'login' => $calvin->user_login,
                    'redirect_to' => '/foo/bar/?baz=foo%20bar',
                ]
            );
        $response->assertRedirect('/auth/login');
        $response->assertSessionHas('login.link.processed');
        
        $this->fake_mailer->assertSent(
            MagicLinkLoginMail::class,
            function (TestableEmail $email) use ($calvin) {
                $body = $email->getHtmlBody();
                return $email->hasTo($calvin)
                       && strpos($body, '/auth/login/magic-link?expires=') !== false
                       && strpos($body, "user_id=$calvin->ID") !== false
                       && strpos(
                              $body,
                              htmlentities('redirect_to='.rawurlencode('/foo/bar/?baz=foo%20bar'))
                          ) !== false;
            }
        );
    }
    
    /** @test */
    public function a_login_email_can_be_request_with_ajax()
    {
        $calvin = $this->createAdmin();
        
        $response = $this->post(
            '/auth/login/magic-link',
            [
                'login' => $calvin->user_login,
                'redirect_to' => '/foo/bar/?baz=foo%20bar',
            ]
            , ['accept' => 'application/json']
        );
        
        $response->assertIsJson()->assertOk();
        
        $this->fake_mailer->assertSentTo($calvin, MagicLinkLoginMail::class);
    }
    
}
