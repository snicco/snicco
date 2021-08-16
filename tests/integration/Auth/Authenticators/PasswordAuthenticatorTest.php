<?php

declare(strict_types=1);

namespace Tests\integration\Auth\Authenticators;

use Tests\AuthTestCase;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Authenticators\PasswordAuthenticator;

class PasswordAuthenticatorTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        
        $this->afterLoadingConfig(function () {
            
            $this->withReplacedConfig('auth.through', [
                PasswordAuthenticator::class,
            ]);
        });
        
        $this->afterApplicationCreated(function () {
            
            $this->url = $this->app->resolve(UrlGenerator::class);
            $this->loadRoutes();
            
        });
        
        parent::setUp();
    }
    
    /** @test */
    public function missing_password_or_login_fails()
    {
        
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            '/auth/login',
            $token + [
                'pwd' => 'password',
            ]
        );
        
        $response->assertRedirect()
                 ->assertSessionHasErrors(
                     ['login' => 'We could not authenticate your credentials.']
                 );
        
        $this->assertGuest();
        
        $token = $this->withCsrfToken();
        
        $response = $this->post('/auth/login', $token + ['log' => 'admin',]);
        
        $response->assertRedirect()
                 ->assertSessionHasErrors(
                     ['login' => 'We could not authenticate your credentials.']
                 );
        
        $this->assertGuest();
        
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            '/auth/login',
            $token + ['log' => '', 'pwd' => '',]
        );
        
        $response->assertRedirect()
                 ->assertSessionHasErrors(
                     ['login' => 'We could not authenticate your credentials.']
                 );
        
        $this->assertGuest();
        
    }
    
    /** @test */
    public function a_non_resolvable_user_throws_an_exception()
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
        
        $response->assertRedirect()
                 ->assertSessionHasErrors(
                     ['login' => 'We could not authenticate your credentials.']
                 )
                 ->assertSessionHasInput(['log' => 'calvin', 'pwd' => 'password']);
        
        $this->assertGuest();
        
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
        
    }
    
}