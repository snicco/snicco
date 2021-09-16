<?php

declare(strict_types=1);

namespace Tests\integration\Auth\Controllers;

use Tests\AuthTestCase;
use Snicco\Events\Event;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Auth\Fail2Ban\TestSysLogger;
use Snicco\Auth\Mail\MagicLinkLoginMail;
use Snicco\Auth\Events\FailedLoginLinkCreationRequest;

class LoginMagicLinkControllerTest extends AuthTestCase
{
    
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
        
        $this->mailFake();
        
        $response = $this->post('/auth/login/magic-link', ['login' => 'bogus']);
        $response->assertRedirect('/auth/login');
        $response->assertSessionHas('login.link.processed');
        
        $this->assertMailNotSent(MagicLinkLoginMail::class);
        
    }
    
    /** @test */
    public function an_event_is_dispatched_for_invalid_logins()
    {
        
        Event::fake([FailedLoginLinkCreationRequest::class]);
        
        $this->post('/auth/login/magic-link', ['login' => 'bogus']);
        
        Event::assertDispatched(
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
        
        $this->mailFake();
        
        $calvin = $this->createAdmin();
        
        $response =
            $this->post(
                '/auth/login/magic-link',
                [
                    'login' => $calvin->user_login,
                    'redirect_to' => '/foo/bar/?baz=foo bar',
                ]
            );
        $response->assertRedirect('/auth/login');
        $response->assertSessionHas('login.link.processed');
        
        $mail = $this->assertMailSent(MagicLinkLoginMail::class);
        $mail->assertTo($calvin);
        $mail->assertSee('/auth/login/magic-link?expires=');
        $mail->assertSee('/auth/login/magic-link?expires=');
        $mail->assertSee("user_id=$calvin->ID");
        $mail->assertSee(htmlentities('redirect_to='.rawurlencode('/foo/bar/?baz=foo bar')));
        
    }
    
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
    
}
