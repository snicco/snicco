<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Controllers;

use Snicco\Auth\Mail\ConfirmAuthMail;
use Snicco\Mail\Testing\TestableEmail;
use Tests\Auth\integration\AuthTestCase;

class AuthConfirmationEmailControllerTest extends AuthTestCase
{
    
    private string $endpoint = '/auth/confirm/magic-link';
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function the_endpoint_exists()
    {
        $this->post($this->endpoint)->assertNotNullResponse();
    }
    
    /** @test */
    public function the_endpoint_cant_be_accessed_if_not_authenticated()
    {
        $token = $this->withCsrfToken();
        $response = $this->post($this->endpoint, $token);
        $response->assertRedirectPath('/auth/login');
    }
    
    /** @test */
    public function the_endpoint_cant_be_accessed_if_auth_is_confirmed()
    {
        $this->actingAs($this->createAdmin());
        $token = $this->withCsrfToken();
        $response = $this->post($this->endpoint, $token);
        $response->assertRedirectToRoute('dashboard');
    }
    
    /** @test */
    public function a_confirmation_email_can_be_requested()
    {
        $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            $this->endpoint,
            $token,
            ['referer' => 'https://foobar.com/auth/confirm']
        );
        
        $response->assertRedirectPath('/auth/confirm');
        $response->assertSessionHas('auth.confirm.email.sent', function ($value) {
            return $value === true;
        });
        $response->assertSessionHas('auth.confirm.email.cool_off', function ($value) {
            return $value === 15;
        });
        
        $this->fake_mailer->assertSentTo($calvin, ConfirmAuthMail::class);
        $this->fake_mailer->assertSent(ConfirmAuthMail::class, function (TestableEmail $mail) {
            return strpos($mail->getHtmlBody(), '/auth/confirm/magic-link?expires=') !== false;
        });
    }
    
    /** @test */
    public function a_confirmation_email_can_be_requested_JSON()
    {
        $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
        $token = $this->withCsrfToken();
        
        $response = $this->post($this->endpoint, $token, ['Accept' => 'application/json']);
        $response->assertStatus(204);
        
        $this->fake_mailer->assertSentTo($calvin, ConfirmAuthMail::class);
    }
    
    /** @test */
    public function users_cant_request_unlimited_emails()
    {
        $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
        $token = $this->withCsrfToken();
        
        $response = $this->post(
            $this->endpoint,
            $token,
            ['referer' => 'https://foobar.com/auth/confirm']
        );
        $response->assertRedirectPath('/auth/confirm');
        
        $this->fake_mailer->assertSentTo($calvin, ConfirmAuthMail::class);
        
        $this->fake_mailer->reset();
        
        $token = $this->withCsrfToken();
        $response =
            $this->post($this->endpoint, $token, ['referer' => 'https://foobar.com/auth/confirm']);
        $response->assertRedirectPath('/auth/confirm')
                 ->assertSessionHasErrors('auth.confirm.email.message')
                 ->assertSessionHas('auth.confirm.email.next');
        
        $this->fake_mailer->assertNotSent(ConfirmAuthMail::class);
        
        $this->fake_mailer->reset();
        
        $this->travelIntoFuture(16);
        $token = $this->withCsrfToken();
        $response =
            $this->post($this->endpoint, $token, ['referer' => 'https://foobar.com/auth/confirm']);
        $response->assertRedirectPath('/auth/confirm')
                 ->assertSessionHasNoErrors();
        
        $this->fake_mailer->assertSentTo($calvin, ConfirmAuthMail::class);
    }
    
}