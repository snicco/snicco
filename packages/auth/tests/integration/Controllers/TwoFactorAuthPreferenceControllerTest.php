<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Controllers;

use Snicco\Shared\Encryptor;
use Tests\Auth\integration\AuthTestCase;
use Tests\Auth\integration\Stubs\TestTwoFactorProvider;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthPreferenceControllerTest extends AuthTestCase
{
    
    private string $endpoint = '/auth/two-factor/preferences';
    
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
    public function two_factor_authentication_can_not_be_confirmed_if_not_pending()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        $this->actingAs($calvin = $this->createAdmin());
        $response = $this->post($this->endpoint, ['Accept' => 'application/json']);
        
        $response->assertStatus(409)
                 ->assertIsJson()
                 ->assertExactJson([
                     'message' => 'Two-Factor authentication is not set-up.',
                 ]);
    }
    
    /** @test */
    public function an_error_is_returned_if_2fa_is_already_enabled_for_the_user()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        $this->actingAs($calvin = $this->createAdmin());
        $this->enable2Fa($calvin);
        
        $response = $this->post($this->endpoint, [], ['Accept' => 'application/json']);
        
        $response->assertStatus(409)
                 ->assertIsJson()
                 ->assertExactJson([
                     'message' => 'Two-Factor authentication is already enabled.',
                 ]);
    }
    
    /** @test */
    public function two_factor_authentication_cant_be_enabled_without_a_valid_one_time_code()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        
        $this->actingAs($calvin = $this->createAdmin());
        $this->generateTestSecret($calvin);
        $this->mark2FaAsPending($calvin);
        
        $response = $this->post($this->endpoint, []);
        $response->assertStatus(400)
                 ->assertExactJson([
                     'message' => 'Invalid or missing code provided.',
                 ]);
        
        $response = $this->post($this->endpoint, [
            'one-time-code' => $this->invalid_one_time_code,
        ]);
        
        $response->assertStatus(400)
                 ->assertExactJson([
                     'message' => 'Invalid or missing code provided.',
                 ]);
    }
    
    /** @test */
    public function two_factor_authentication_can_be_enabled()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        $this->actingAs($calvin = $this->createAdmin());
        $this->generateTestSecret($calvin)
             ->mark2FaAsPending($calvin);
        
        $this->assertSame('', get_user_meta($calvin->ID, 'two_factor_active', true));
        $this->assertSame('1', get_user_meta($calvin->ID, 'two_factor_pending', true));
        
        $response = $this->post($this->endpoint, ['one-time-code' => $this->valid_one_time_code]);
        $response->assertOk();
        
        $body = json_decode($response->body(), true);
        $this->assertCount(8, $body);
        $codes_in_db = $this->getUserRecoveryCodes($calvin);
        $this->assertSame($codes_in_db, $body);
        
        $this->assertSame('', get_user_meta($calvin->ID, 'two_factor_pending', true));
        $this->assertSame('1', get_user_meta($calvin->ID, 'two_factor_active', true));
    }
    
    /** @test */
    public function recovery_codes_are_encrypted()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        $this->actingAs($calvin = $this->createAdmin());
        $this->generateTestSecret($calvin)
             ->mark2FaAsPending($calvin);
        
        $response = $this->post($this->endpoint, ['one-time-code' => $this->valid_one_time_code]);
        $response->assertOk();
        
        $codes_in_body = json_decode($response->body(), true);
        $this->assertCount(8, $codes_in_body);
        
        $codes_in_db = get_user_meta($calvin->ID, 'two_factor_recovery_codes', true);
        $this->assertNotSame($codes_in_db, $codes_in_body);
        
        $this->assertSame(
            $codes_in_body,
            json_decode($this->encryptor->decrypt($codes_in_db), true)
        );
    }
    
    /** @test */
    public function two_factor_authentication_can_not_be_disabled_for_user_who_dont_have_it_enabled()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        
        $this->actingAs($calvin = $this->createAdmin());
        
        $response = $this->delete($this->endpoint);
        $response->assertStatus(403)->assertExactJson([
            'message' => 'Two-Factor-Authentication needs to be enabled for your account to perform this action.',
        ]);
    }
    
    /** @test */
    public function two_factor_authentication_can_be_disabled()
    {
        $this->bootApp();
        $this->withoutMiddleware('csrf');
        
        $this->actingAs($calvin = $this->createAdmin());
        $this->generateTestSecret($calvin);
        update_user_meta(
            $calvin->ID,
            'two_factor_recovery_codes',
            $this->encryptCodes($this->generateTestRecoveryCodes())
        );
        update_user_meta($calvin->ID, 'two_factor_active', true);
        
        $response = $this->delete($this->endpoint);
        $response->assertNoContent();
        
        $this->assertEmpty($this->getUserSecret($calvin));
        $this->assertEmpty($this->getUserRecoveryCodes($calvin));
        $this->assertEmpty(get_user_meta($calvin->ID, 'two_factor_active', true));
    }
    
}
    
    