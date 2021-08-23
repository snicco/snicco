<?php

declare(strict_types=1);

namespace Tests\integration\Auth\Controllers;

use Tests\AuthTestCase;
use Snicco\Events\Event;
use Tests\stubs\TestApp;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Auth\Mail\ResetPasswordMail;
use Snicco\Auth\Fail2Ban\TestSysLogger;
use Snicco\Auth\Events\FailedPasswordResetLinkRequest;

class ForgotPasswordControllerTest extends AuthTestCase
{
    
    /** @test */
    public function the_route_cant_be_accessed_while_being_logged_in()
    {
        
        $this->bootApp();
        
        $this->actingAs($this->createAdmin());
        
        $this->get($this->routeUrl())->assertRedirectToRoute('dashboard');
        
    }
    
    /** @test */
    public function the_forgot_password_view_can_be_rendered()
    {
        $this->bootApp();
        $this->get($this->routeUrl())
             ->assertSee('Request new password')
             ->assertOk();
        
    }
    
    /** @test */
    public function the_endpoint_is_not_accessible_when_disabled_in_the_config()
    {
        
        $this->withOutConfig('auth.features.password-resets');
        $this->bootApp();
        
        $url = '/auth/forgot-password';
        
        $this->get($url)->assertDelegatedToWordPress();
        
    }
    
    /** @test */
    public function a_password_reset_link_can_be_requested_by_user_name()
    {
        $this->bootApp();
        
        $this->mailFake();
        $token = $this->withCsrfToken();
        
        $url = $this->routeUrl();
        
        $calvin = $this->createAdmin();
        
        $response = $this->post($url, $token + ['login' => $calvin->user_login]);
        $response->assertRedirectToRoute('auth.forgot.password');
        
        $mail = $this->assertMailSent(ResetPasswordMail::class);
        
        $expected_link = TestApp::url()->toRoute('auth.reset.password', [], true, true);
        
        $mail->assertTo($calvin)
             ->assertView('password-forgot-email')
             ->assertSee("$expected_link?expires=");
        
    }
    
    /** @test */
    public function a_password_reset_link_can_be_requested_by_user_email()
    {
        $this->bootApp();
        
        $this->mailFake();
        $token = $this->withCsrfToken();
        
        $url = $this->routeUrl();
        
        $calvin = $this->createAdmin();
        
        $response = $this->post($url, $token + ['login' => $calvin->user_email]);
        $response->assertRedirectToRoute('auth.forgot.password');
        $response->assertSessionHas('password.reset.processed', true);
        
        $mail = $this->assertMailSent(ResetPasswordMail::class);
        
        $expected_link = TestApp::url()->toRoute('auth.reset.password', [], true, true);
        
        $mail->assertTo($calvin)
             ->assertView('password-forgot-email')
             ->assertSee("$expected_link?expires=");
        
    }
    
    /** @test */
    public function invalid_input_does_not_return_an_error_message_but_doesnt_send_an_email()
    {
        $this->bootApp();
        
        $this->mailFake();
        $token = $this->withCsrfToken();
        
        $response = $this->post($this->routeUrl(), $token + ['login' => 'bogus@web.de']);
        
        $response->assertRedirectToRoute('auth.forgot.password');
        $this->assertMailNotSent(ResetPasswordMail::class);
        
    }
    
    /** @test */
    public function failure_to_retrieve_a_user_by_login_will_dispatch_an_event()
    {
        $this->bootApp();
        
        Event::fake();
        $token = $this->withCsrfToken();
        
        $response = $this->post($this->routeUrl(), $token + ['login' => 'bogus@web.de']);
        
        $response->assertRedirectToRoute('auth.forgot.password');
        Event::assertDispatched(
            FailedPasswordResetLinkRequest::class,
            function (FailedPasswordResetLinkRequest $event) {
                
                return $event->request()->post('login') === 'bogus@web.de';
                
            }
        );
        
    }
    
    /** @test */
    public function an_invalid_user_login_will_log_to_fail2ban()
    {
        $this->bootApp();
        
        $this->swap(Syslogger::class, $logger = new TestSysLogger());
        
        $token = $this->withCsrfToken();
        $this->default_attributes = ['ip_address' => '127.0.0.1'];
        $response = $this->post($this->routeUrl(), $token + ['login' => 'bogus@web.de']);
        
        $response->assertRedirectToRoute('auth.forgot.password');
        $response->assertSessionHas('password.reset.processed', true);
        $logger->assertLogEntry(
            E_NOTICE,
            'User enumeration trying to request a new password for user login [bogus@web.de] from 127.0.0.1'
        );
        
    }
    
    protected function setUp() :void
    {
        
        $this->afterApplicationCreated(function () {
            
            $this->withAddedConfig('auth.features.password-resets', true);
            $this->withAddedConfig('auth.fail2ban.enabled', true);
            
        });
        
        parent::setUp();
        
    }
    
    private function routeUrl()
    {
        
        ;
        
        return TestApp::url()->signedRoute('auth.forgot.password');
    }
    
}