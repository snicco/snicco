<?php

declare(strict_types=1);

namespace Tests\Auth\integration\Authenticators;

use Snicco\Shared\Encryptor;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Fail2Ban\TestSysLogger;
use Tests\Auth\integration\AuthTestCase;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Auth\Events\FailedTwoFactorAuthentication;
use Snicco\Auth\Authenticators\TwoFactorAuthenticator;
use Tests\Auth\integration\Stubs\TestTwoFactorProvider;
use Snicco\Auth\Authenticators\RedirectIf2FaAuthenticable;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;
use Snicco\Auth\Contracts\AbstractTwoFactorChallengeResponse;

class TwoFactorAuthenticatorTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->with2Fa();
            
            $this->withReplacedConfig('auth.through',
                [
                    TwoFactorAuthenticator::class,
                    RedirectIf2FaAuthenticable::class,
                    TestAuthenticator::class,
                ]
            );
            
            $this->withAddedConfig('auth.fail2ban.enabled', true);
        });
        
        $this->afterApplicationBooted(function () {
            $this->withoutMiddleware('csrf');
            $this->instance(
                AbstractTwoFactorChallengeResponse::class,
                $this->app->resolve(TestChallengeResponse::class)
            );
            $this->encryptor = $this->app->resolve(Encryptor::class);
            $this->instance(
                TwoFactorAuthenticationProvider::class,
                new TestTwoFactorProvider($this->encryptor)
            );
        });
        
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function any_non_login_response_is_returned_as_is()
    {
        $response = $this->post('/auth/login');
        
        $response->assertRedirectToRoute('auth.login');
        $this->assertGuest();
    }
    
    /** @test */
    public function a_successfully_authenticated_user_is_logged_in_if_he_doesnt_have_2fa_enabled()
    {
        $calvin = $this->createAdmin();
        $this->assertNotAuthenticated($calvin);
        
        $response = $this->post('/auth/login', [
            'login' => $calvin->user_login,
        ]);
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
    }
    
    /** @test */
    public function a_user_with_2fa_enabled_is_challenged()
    {
        $this->withAddedConfig('auth.features.remember_me', 10);
        
        $calvin = $this->createAdmin();
        $this->generateTestSecret($calvin);
        $this->enable2Fa($calvin);
        $this->assertNotAuthenticated($calvin);
        
        $response = $this->post('/auth/login', [
            'login' => $calvin->user_login,
            'remember_me' => 1,
        ]);
        
        $response->assertSee('[test] Please enter your 2fa code.');
        $response->assertSessionHas(['auth.2fa.remember' => true]);
        $this->assertSame($calvin->ID, $response->session()->challengedUser());
        $this->assertNotAuthenticated($calvin);
    }
    
    /** @test */
    public function a_challenged_user_without_2fa_enabled_does_not_get_the_2fa_challenge_view()
    {
        $calvin = $this->createAdmin();
        
        // For some reason calvin is challenged but does not use 2fa.
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        
        $response = $this->post('/auth/login', [
            'login' => $calvin->user_login,
        ]);
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
    }
    
    /** @test */
    public function a_user_cant_login_with_an_invalid_one_time_code()
    {
        $calvin = $this->createAdmin();
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        $this->generateTestSecret($calvin);
        $this->enable2Fa($calvin);
        
        $response = $this->post('/auth/login', [
            'one-time-code' => $this->invalid_one_time_code,
        ], ['referer' => '/auth/two-factor/challenge']);
        
        $response->assertRedirectToRoute('auth.2fa.challenge');
        $response->assertSessionHasErrors('login');
        $this->assertNotAuthenticated($calvin);
        $this->dispatcher->assertDispatched(
            fn(FailedTwoFactorAuthentication $event) => $event->userId() === $calvin->ID
        );
    }
    
    /** @test */
    public function a_failed_login_will_be_recorded_with_fail2ban()
    {
        $this->swap(Syslogger::class, $logger = new TestSysLogger());
        
        $this->default_attributes = ['ip_address' => '127.0.0.1'];
        $calvin = $this->createAdmin();
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        $this->generateTestSecret($calvin);
        $this->enable2Fa($calvin);
        
        $response = $this->post('/auth/login', [
            'one-time-code' => $this->invalid_one_time_code,
        ], ['referer' => '/auth/two-factor/challenge']);
        
        $response->assertRedirectToRoute('auth.2fa.challenge');
        $response->assertSessionHasErrors('login');
        $this->assertNotAuthenticated($calvin);
        
        $logger->assertLogEntry(
            LOG_WARNING,
            "Failed two-factor authentication for user [$calvin->ID] from 127.0.0.1"
        );
    }
    
    /** @test */
    public function a_user_can_login_with_a_valid_one_time_code()
    {
        $calvin = $this->createAdmin();
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        $this->generateTestSecret($calvin);
        $this->enable2Fa($calvin);
        
        $response = $this->post('/auth/login', [
            'one-time-code' => $this->valid_one_time_code,
        ]);
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
        $response->assertSessionMissing('auth.2fa');
    }
    
    /** @test */
    public function the_user_can_log_in_with_a_valid_recovery_codes()
    {
        $calvin = $this->createAdmin();
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        $this->enable2Fa($calvin);
        
        $codes = $this->generateTestRecoveryCodes();
        update_user_meta(
            $calvin->ID,
            'two_factor_recovery_codes',
            $this->encryptor->encrypt(json_encode($codes))
        );
        $this->generateTestSecret($calvin);
        
        $response = $this->post('/auth/login', [
            'recovery-code' => 'bogus',
        ], ['referer' => '/auth/two-factor/challenge']);
        
        $response->assertRedirectToRoute('auth.2fa.challenge');
        $this->assertNotAuthenticated($calvin);
        
        $response = $this->post('/auth/login', [
            'recovery-code' => $codes[0],
        ]);
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
        $response->assertSessionMissing('auth.2fa');
    }
    
    /** @test */
    public function the_recovery_code_is_swapped_on_successful_use()
    {
        $calvin = $this->createAdmin();
        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
        $this->enable2Fa($calvin);
        
        $codes = $this->generateTestRecoveryCodes();
        
        update_user_meta(
            $calvin->ID,
            'two_factor_recovery_codes',
            $this->encryptor->encrypt(json_encode($codes))
        );
        $this->generateTestSecret($calvin);
        
        $response = $this->post('/auth/login', [
            'recovery-code' => $code = $codes[0],
        ]);
        
        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated($calvin);
        
        $codes = get_user_meta($calvin->ID, 'two_factor_recovery_codes', true);
        $codes = json_decode($this->encryptor->decrypt($codes), true);
        
        $this->assertNotContains($code, $codes);
    }
    
}

class TestAuthenticator extends Authenticator
{
    
    use ResolvesUser;
    
    public function attempt(Request $request, $next) :Response
    {
        if ( ! $request->has('login')) {
            return $this->response_factory->redirect()->toRoute('auth.login');
        }
        
        $user = $request->input('login');
        $user = $this->getUserByLogin($user);
        
        return $this->login($user);
    }
    
}

class TestChallengeResponse extends AbstractTwoFactorChallengeResponse
{
    
    public function toResponsable()
    {
        return '[test] Please enter your 2fa code.';
    }
    
}
