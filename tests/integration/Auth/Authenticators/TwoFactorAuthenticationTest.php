<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Authenticators;

    use Tests\AuthTestCase;
    use Tests\integration\Auth\Stubs\TestTwoFactorProvider;
    use BetterWP\Auth\Authenticators\RedirectIf2FaAuthenticable;
    use BetterWP\Auth\Authenticators\TwoFactorAuthenticator;
    use BetterWP\Auth\Contracts\Authenticator;
    use BetterWP\Auth\Contracts\TwoFactorAuthenticationProvider;
    use BetterWP\Auth\Contracts\TwoFactorChallengeResponse;
    use BetterWP\Auth\Middleware\AuthenticateSession;
    use BetterWP\Auth\Traits\ResolvesUser;
    use BetterWP\Contracts\EncryptorInterface;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;


    class TwoFactorAuthenticationTest extends AuthTestCase
    {

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->with2Fa();

                $this->withReplacedConfig('auth.through',
                    [
                        TwoFactorAuthenticator::class,
                        RedirectIf2FaAuthenticable::class,
                        TestAuthenticator::class,
                    ]
                );

            });

            $this->afterApplicationCreated(function () {

                $this->withoutMiddleware('csrf');
                $this->withoutMiddleware(AuthenticateSession::class);
                $this->instance(
                    TwoFactorChallengeResponse::class,
                    $this->app->resolve(TestChallengeResponse::class)
                );
                $this->instance(TwoFactorAuthenticationProvider::class, new TestTwoFactorProvider());
                $this->encryptor = $this->app->resolve(EncryptorInterface::class);
            });

            parent::setUp();
        }

        /** @test */
        public function any_non_login_response_is_returned_as_is()
        {

            $response = $this->post('/auth/login');

            $response->assertRedirectToRoute('auth.login');
            $this->assertGuest();;

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
            $this->withoutExceptionHandling();

            $calvin = $this->createAdmin();
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');
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
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/login', [
                'token' => $this->invalid_one_time_code,
            ]);

            $response->assertRedirectToRoute('auth.2fa.challenge');
            $this->assertNotAuthenticated($calvin);

        }

        /** @test */
        public function a_user_can_login_with_a_valid_one_time_code()
        {

            $this->withoutExceptionHandling();

            $calvin = $this->createAdmin();
            $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/login', [
                'token' => $this->valid_one_time_code,
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
            $codes = $this->createCodes();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptor->encrypt(json_encode($codes)));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/login', [
                'recovery-code' => 'bogus',
            ]);

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
            $codes = $this->createCodes();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptor->encrypt(json_encode($codes)));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

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

        public function attempt(Request $request, $next) : Response
        {

            if ( ! $request->has('login') ) {
                return $this->response_factory->redirect()->toRoute('auth.login');
            }

            $user = $request->input('login');
            $user = $this->getUserByLogin($user);

            return $this->login($user);

        }

    }

    class TestChallengeResponse extends TwoFactorChallengeResponse
    {

        public function toResponsable()
        {
            return '[test] Please enter your 2fa code.';

        }

    }
