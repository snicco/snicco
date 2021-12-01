<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Authenticators;

use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Auth\Fail2Ban\TestSysLogger;
use Tests\Auth\integration\AuthTestCase;
use Snicco\Auth\Events\FailedPasswordAuthentication;
use Snicco\Auth\Authenticators\PasswordAuthenticator;

class PasswordAuthenticatorTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withReplacedConfig('auth.through', [
                PasswordAuthenticator::class,
            ]);
            $this->withAddedConfig('auth.fail2ban.enabled', true);
        });
        
        $this->afterApplicationBooted(function () {
            $this->url = $this->app->resolve(UrlGenerator::class);
        });
        
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function missing_password_or_login_delegates_to_the_next_authenticator()
    {
        $token = $this->withCsrfToken();
        $response = $this->post('/auth/login', $token + ['pwd' => 'password',]);
        
        // missing login
        $response->assertRedirectPath('/auth/login')
                 ->assertSessionHasErrors('login');
        $this->assertGuest();
        
        // missing password
        $token = $this->withCsrfToken();
        $response = $this->post('/auth/login', $token + ['log' => 'admin',]);
        
        $response->assertRedirectPath('/auth/login')
                 ->assertSessionHasErrors('login');
        $this->assertGuest();
        
        $token = $this->withCsrfToken();
        $response = $this->post(
            '/auth/login',
            $token + ['log' => '', 'pwd' => '',]
        );
        
        // both password
        $response->assertRedirectPath('/auth/login')
                 ->assertSessionHasErrors('login');
        $this->assertGuest();
    }
    
    /** @test */
    public function a_non_existing_user_dispatches_an_event_and_fails()
    {
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            '/auth/login',
            $token +
            [
                'log' => 'calvin',
                'pwd' => 'password',
            ]
        );
        
        $this->dispatcher->assertDispatched(function (FailedPasswordAuthentication $event) {
            return $event->login() === 'calvin';
        });
        
        $response->assertRedirectPath('/auth/login')
                 ->assertSessionHasErrors('login');
        $this->assertGuest();
    }
    
    /** @test */
    public function a_wrong_password_for_an_existing_user_dispatches_an_event_and_fails()
    {
        $calvin = $this->createAdmin();
        
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            '/auth/login',
            $token +
            [
                'log' => $calvin->user_login,
                'pwd' => 'bogus',
            ]
        );
        
        $this->assertGuest();
        $response->assertRedirectPath('/auth/login')
                 ->assertSessionHasErrors('login');
        
        $this->dispatcher->assertDispatched(
            function (FailedPasswordAuthentication $event) use ($calvin) {
                return $event->login() === $calvin->user_login && $event->password() === 'bogus';
            }
        );
    }
    
    /** @test */
    public function a_user_can_login_with_valid_credentials()
    {
        $this->withAddedConfig('auth.features.remember_me', true);
        
        $calvin = $this->createAdmin();
        
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            '/auth/login',
            $token +
            [
                'log' => $calvin->user_login,
                'pwd' => 'password',
                'remember_me' => '1',
            ]
        );
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
        $this->assertTrue($this->session->hasRememberMeToken());
        $this->dispatcher->assertNotDispatched(FailedPasswordAuthentication::class);
    }
    
    /** @test */
    public function a_user_can_login_with_his_email_address_instead_of_the_username()
    {
        $this->withAddedConfig('auth.features.remember_me', true);
        
        $calvin = $this->createAdmin();
        
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            '/auth/login',
            $token +
            [
                'log' => $calvin->user_email,
                'pwd' => 'password',
                'remember_me' => '1',
            ]
        );
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
        $this->assertTrue($this->session->hasRememberMeToken());
        $this->dispatcher->assertNotDispatched(FailedPasswordAuthentication::class);
    }
    
    /** @test */
    public function a_failed_login_is_logged_with_fail2ban()
    {
        $this->swap(Syslogger::class, $logger = new TestSysLogger());
        
        $this->default_attributes = ['ip_address' => '127.0.0.1'];
        $calvin = $this->createAdmin();
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            '/auth/login',
            $token +
            [
                'log' => $calvin->user_login,
                'pwd' => 'bogus',
            ]
        );
        
        $this->assertGuest();
        $response->assertRedirectPath('/auth/login')
                 ->assertSessionHasErrors('login');
        
        $logger->assertLogEntry(
            LOG_WARNING,
            "Failed authentication attempt for user [$calvin->ID] with invalid password [bogus] from 127.0.0.1"
        );
    }
    
}