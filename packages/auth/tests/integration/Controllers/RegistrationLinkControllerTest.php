<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Controllers;

use Snicco\Mail\Testing\TestableEmail;
use Tests\Auth\integration\AuthTestCase;
use Snicco\Auth\Mail\ConfirmRegistrationEmail;
use Snicco\Auth\Contracts\AbstractRegistrationView;

class RegistrationLinkControllerTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withAddedConfig('auth.features.registration', true);
        });
        
        $this->afterApplicationBooted(function () {
            $this->withoutMiddleware('csrf');
            $this->instance(AbstractRegistrationView::class, new TestRegistrationViewView());
        });
        
        parent::setUp();
    }
    
    /** @test */
    public function the_route_cant_be_accessed_if_registration_is_not_enabled()
    {
        $this->withOutConfig('auth.features.registration')->bootApp();
        
        $response = $this->get('/auth/register');
        
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_route_cant_be_accessed_authenticated()
    {
        $this->actingAs($this->createAdmin())->bootApp();
        
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
        $this->bootApp();
        
        $response =
            $this->post('/auth/register', ['email' => 'c@web.de'], ['referer' => '/auth/register']);
        
        $response->assertRedirect('/auth/register')->assertSessionHasNoErrors();
        $response->assertSessionHas('registration.link.success', true);
        $response->assertSessionHas('registration.email', 'c@web.de');
        
        $this->fake_mailer->assertSent(
            ConfirmRegistrationEmail::class,
            function (TestableEmail $email) {
                return $email->hasTo('c@web.de')
                       && strpos(
                           $email->getHtmlBody(),
                           '/auth/accounts/create?expires='
                       );
            }
        );
    }
    
}

class TestRegistrationViewView extends AbstractRegistrationView
{
    
    public function toResponsable() :string
    {
        return '[Test] Register now.';
    }
    
}