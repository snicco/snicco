<?php

declare(strict_types=1);

namespace Tests\integration\Auth\Controllers;

use Tests\AuthTestCase;
use Snicco\Auth\Mail\ConfirmRegistrationEmail;
use Snicco\Auth\Contracts\RegistrationViewResponse;

class RegistrationLinkControllerTest extends AuthTestCase
{
    
    /** @test */
    public function the_route_cant_be_accessed_if_registration_is_not_enabled()
    {
        
        $this->withOutConfig('auth.features.registration');
        
        $response = $this->get('/auth/register');
        
        $response->assertNullResponse();
        
    }
    
    /** @test */
    public function the_route_cant_be_accessed_authenticated()
    {
        
        $this->actingAs($this->createAdmin());
        
        $response = $this->get('/auth/register');
        
        $response->assertRedirectToRoute('dashboard');
        
    }
    
    /** @test */
    public function the_registration_view_can_be_rendered()
    {
        
        $response = $this->get('/auth/register');
        
        $response->assertOk()->assertSee('[Test] Register now.');
        
    }
    
    /** @test */
    public function a_link_cant_be_requested_with_invalid_email()
    {
        
        $response =
            $this->post('/auth/register', ['email' => 'bogus.de'], ['referer' => '/auth/register']);
        $response->assertRedirect('/auth/register')->assertSessionHasErrors('email');
        
    }
    
    /** @test */
    public function a_link_can_be_requested_for_a_valid_email()
    {
        
        $this->mailFake();
        
        $response =
            $this->post('/auth/register', ['email' => 'c@web.de'], ['referer' => '/auth/register']);
        
        $response->assertRedirect('/auth/register')->assertSessionHasNoErrors();
        $response->assertSessionHas('registration.link.success', true);
        $response->assertSessionHas('registration.email', 'c@web.de');
        
        $mail = $this->assertMailSent(ConfirmRegistrationEmail::class);
        $mail->assertTo('c@web.de');
        $mail->assertSee('/auth/accounts/create?expires=');
        
    }
    
    protected function setUp() :void
    {
        
        $this->afterLoadingConfig(function () {
            
            $this->withAddedConfig('auth.features.registration', true);
            
        });
        
        $this->afterApplicationCreated(function () {
            
            $this->withoutMiddleware('csrf');
            $this->instance(RegistrationViewResponse::class, new TestRegistrationView());
        });
        
        parent::setUp();
        
    }
    
}

class TestRegistrationView extends RegistrationViewResponse
{
    
    public function toResponsable() :string
    {
        
        return '[Test] Register now.';
    }
    
}