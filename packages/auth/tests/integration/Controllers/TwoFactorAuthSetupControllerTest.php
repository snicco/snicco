<?php

namespace Tests\Auth\integration\Controllers;

use Snicco\Shared\Encryptor;
use PragmaRX\Google2FA\Google2FA;
use Tests\Auth\integration\AuthTestCase;
use Snicco\Auth\Google2FaAuthenticationProvider;
use Tests\Auth\integration\Stubs\TestTwoFactorProvider;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthSetupControllerTest extends AuthTestCase
{
    
    private string $endpoint = '/auth/two-factor/setup';
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->with2Fa();
        });
        
        $this->afterApplicationBooted(function () {
            $this->withHeader('Accept', 'application/json');
            $this->encryptor = $this->app->resolve(Encryptor::class);
            $this->swap(
                TwoFactorAuthenticationProvider::class,
                new TestTwoFactorProvider($this->encryptor)
            );
        });
        
        parent::setUp();
    }
    
    /** @test */
    public function the_endpoint_is_not_accessible_with_2fa_disabled()
    {
        $this->without2Fa()->bootApp();
        
        $this->post($this->endpoint)->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_endpoint_is_not_accessible_if_not_authenticated()
    {
        $this->bootApp();
        
        $this->post($this->endpoint)
             ->assertStatus(401);
    }
    
    /** @test */
    public function the_endpoint_is_not_accessible_if_auth_confirmation_is_expired()
    {
        $this->bootApp();
        
        $this->actingAs($calvin = $this->createAdmin());
        
        $this->travelIntoFuture(10);
        
        $this->post($this->endpoint)->assertRedirectToRoute('auth.confirm');
    }
    
    /** @test */
    public function the_endpoint_is_not_accessible_without_csrf_tokens()
    {
        $this->bootApp();
        
        $this->actingAs($calvin = $this->createAdmin());
        
        $this->post($this->endpoint, [])->assertStatus(419);
    }
    
    /** @test */
    public function the_initial_setup_qr_code_can_be_rendered()
    {
        $this->bootApp();
        $this->actingAs($calvin = $this->createAdmin());
        $token = $this->withCsrfToken();
        $response = $this->post($this->endpoint, $token)->assertStatus(200);
        
        $response_body = json_decode($response->body(), true);
        
        $this->assertSame('secret', $this->encryptor->decrypt($this->getUserSecret($calvin)));
        $this->assertSame('secret', $response_body['secret']);
        
        // "code" comes from the TestTwoFactorProvider::class.
        $this->assertSame('code', $response_body['qr_code']);
        
        // 2fa is not confirmed yet.
        $this->assertSame('', get_user_meta($calvin->ID, 'two_factor_active', true));
    }
    
    /** @test */
    public function an_error_is_returned_if_2fa_is_already_enabled_for_the_user()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        $this->actingAs($calvin = $this->createAdmin());
        $this->generateTestSecret($calvin);
        $token = $this->withCsrfToken();
        update_user_meta($calvin->ID, 'two_factor_active', true);
        
        $response = $this->post($this->endpoint, $token, ['Accept' => 'application/json']);
        
        $response->assertStatus(409)
                 ->assertIsJson()
                 ->assertExactJson([
                     'message' => 'Two-Factor authentication is already enabled.',
                 ]);
    }
    
    /** @test */
    public function the_auth_set_up_is_marked_as_pending()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        $this->actingAs($calvin = $this->createAdmin());
        $token = $this->withCsrfToken();
        
        $response = $this->post($this->endpoint, $token, ['Accept' => 'application/json']);
        
        $response->assertStatus(200);
        
        $this->assertSame('1', get_user_meta($calvin->ID, 'two_factor_pending', true));
    }
    
    /** @test */
    public function the_user_secret_is_stored_encrypted()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        $this->actingAs($calvin = $this->createAdmin());
        $token = $this->withCsrfToken();
        
        $response = $this->post($this->endpoint, $token, ['Accept' => 'application/json']);
        
        $response->assertStatus(200);
        
        // Assert
        $this->assertNotSame(
            'secret',
            $key = get_user_meta($calvin->ID, 'two_factor_secret', true),
            'Two factor secret stored as plain text.'
        );
        $this->assertSame('secret', $this->encryptor->decrypt($key));
    }
    
    /** @test */
    public function the_setup_qr_code_and_secret_can_be_generated_any_amount_of_times()
    {
        $this->bootApp();
        
        $this->swap(
            TwoFactorAuthenticationProvider::class,
            new Google2FaAuthenticationProvider(new Google2FA(), $this->encryptor)
        );
        
        $this->actingAs($calvin = $this->createAdmin());
        $token = $this->withCsrfToken();
        $response1 = $this->post($this->endpoint, $token)->assertStatus(200);
        
        $response_body1 = json_decode($response1->body(), true);
        $secret_one = $response_body1['secret'];
        $qr_conde_one = $response_body1['qr_code'];
        
        $token = $this->withCsrfToken();
        $response2 = $this->post($this->endpoint, $token)->assertStatus(200);
        $response_body2 = json_decode($response2->body(), true);
        
        $secret_two = $response_body2['secret'];
        $qr_conde_two = $response_body2['qr_code'];
        
        $this->assertNotSame($secret_one, $secret_two);
        $this->assertNotSame($qr_conde_one, $qr_conde_two);
    }
    
}