<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Controllers;

use Snicco\Shared\Encryptor;
use Tests\Auth\integration\AuthTestCase;

use function update_user_meta;

class TwoFactorAuthSessionControllerTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->with2Fa();
        });
        $this->afterApplicationBooted(function () {
            $this->encryptor = $this->app->resolve(Encryptor::class);
        });
        
        parent::setUp();
    }
    
    /** @test */
    public function the_controller_cant_be_accessed_if_2fa_is_disabled()
    {
        $this->without2Fa()->bootApp();
        
        $response = $this->get('/auth/two-factor/challenge');
        
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_auth_challenge_is_not_rendered_if_a_user_is_not_challenged()
    {
        $this->bootApp();
        $calvin = $this->createAdmin();
        update_user_meta($calvin->ID, 'two_factor_secret', 'secret');
        
        $response = $this->get('/auth/two-factor/challenge');
        
        $response->assertRedirectToRoute('auth.login');
    }
    
    /** @test */
    public function the_auth_challenge_is_not_rendered_if_a_user_is_not_using_2fa()
    {
        $this->bootApp();
        
        $calvin = $this->createAdmin();
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        
        $response = $this->get('/auth/two-factor/challenge');
        
        $response->assertRedirectToRoute('auth.login');
    }
    
    /** @test */
    public function the_2fa_challenge_screen_can_be_rendered()
    {
        $this->bootApp();
        
        $calvin = $this->createAdmin();
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        $this->generateTestSecret($calvin);
        $this->enable2Fa($calvin);
        
        $response = $this->get('/auth/two-factor/challenge');
        
        $response->assertOk();
        $response->assertSee('Code');
        $response->assertSeeHtml('/auth/login');
    }
    
}