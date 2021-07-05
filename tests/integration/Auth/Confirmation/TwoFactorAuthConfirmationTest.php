<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Confirmation;

    use Tests\AuthTestCase;
    use Tests\integration\Auth\Stubs\TestTwoFactorProvider;
    use WPMvc\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPMvc\Contracts\EncryptorInterface;
    use WPMvc\Routing\UrlGenerator;

    class TwoFactorAuthConfirmationTest extends AuthTestCase
    {

        protected function setUp() : void
        {

            $this->afterApplicationCreated(function () {

                $this->with2Fa();
                $this->loadRoutes();
                $this->instance(TwoFactorAuthenticationProvider::class, new TestTwoFactorProvider());
                $this->withoutMiddleware('csrf');
                $this->encryptor = $this->app->resolve(EncryptorInterface::class);

            });
            parent::setUp();
        }

        private function validEmailConfirmMagicLink() : string
        {

            /** @var UrlGenerator $url */
            $url = $this->app->resolve(UrlGenerator::class);

            return $url->signedRoute('auth.confirm.magic-link', [], true, true);
        }

        /** @test */
        public function the_email_confirmation_view_is_used_if_the_current_user_doesnt_have_2fa_enabled()
        {

            $this->authenticateAndUnconfirm($this->createAdmin());

            $response = $this->get('/auth/confirm');

            $response->assertOk();
            $response->assertSee('You need to confirm your access before you can proceed.');
            $response->assertViewHas('post_to', function ($data) {

                return $data === '/auth/confirm/email';
            });

        }

        /** @test */
        public function the_two_factor_auth_challenge_view_is_used_if_the_user_has_2fa_enabled()
        {

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->get('/auth/confirm');

            $response->assertOk();
            $response->assertSee('authentication code');
            $response->assertViewHas('post_to', function ($data) {

                return $data === '/auth/confirm';
            });
            $response->assertViewHas('view', 'auth-two-factor-challenge');

        }

        /** @test */
        public function the_fall_back_authenticator_is_used_if_the_user_doesnt_have_2fa_enabled()
        {

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());

            $response = $this->get($this->validEmailConfirmMagicLink());

            $response->assertRedirectToRoute('dashboard');

            $this->assertTrue($response->session()->hasValidAuthConfirmToken());

        }

        /** @test */
        public function the_fall_back_authenticator_cant_be_used_if_the_user_doesnt_have_2fa_enabled()
        {

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->get($this->validEmailConfirmMagicLink());

            $response->assertRedirect('/auth/confirm');
            $response->assertSessionHasErrors();

            $this->assertFalse($response->session()->hasValidAuthConfirmToken());

        }

        /** @test */
        public function a_user_cant_confirm_auth_with_an_invalid_one_time_code()
        {

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/confirm', [
                'token' => $this->invalid_one_time_code,
            ]);

            $response->assertRedirectToRoute('auth.confirm');
            $response->assertSessionHasErrors(['message' => 'Invalid code provided.']);
            $this->assertFalse($response->session()->hasValidAuthConfirmToken());


        }

        /** @test */
        public function a_user_can_confirm_auth_with_a_valid_one_time_code()
        {

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/confirm', [
                'token' => $this->valid_one_time_code,
            ]);

            $response->assertRedirectToRoute('dashboard');
            $response->assertSessionHasNoErrors();
            $this->assertTrue($response->session()->hasValidAuthConfirmToken());

        }

        /** @test */
        public function the_user_can_confirm_auth_with_a_valid_recovery_codes()
        {

            $this->withoutExceptionHandling();

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            $codes = $this->createCodes();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptor->encrypt(json_encode($codes)));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/confirm', [
                'recovery-code' => 'bogus',
            ]);

            $response->assertRedirectToRoute('auth.confirm');
            $this->assertFalse($response->session()->hasValidAuthConfirmToken());
            $response->assertSessionHasErrors('message');

            $response = $this->post('/auth/confirm', [
                'recovery-code' => $codes[0],
            ]);

            $response->assertRedirectToRoute('dashboard');
            $this->assertTrue($response->session()->hasValidAuthConfirmToken());
            $response->assertSessionHasNoErrors();

        }

        /** @test */
        public function the_recovery_code_is_swapped_on_successful_use()
        {

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            $codes = $this->createCodes();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptor->encrypt(json_encode($codes)));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/confirm', [
                'recovery-code' => $code = $codes[0],
            ]);

            $response->assertRedirectToRoute('dashboard');
            $this->assertTrue($response->session()->hasValidAuthConfirmToken());
            $response->assertSessionHasNoErrors();

            $codes = get_user_meta($calvin->ID, 'two_factor_recovery_codes', true);
            $codes = json_decode($this->encryptor->decrypt($codes), true);

            $this->assertNotContains($code, $codes);


        }

        /** @test */
        public function errors_are_rendered_in_the_view () {

            $this->followingRedirects();
            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post('/auth/confirm', [
                'token' => $this->invalid_one_time_code,
            ]);

            $response->assertOk()->assertSee('Invalid code provided.');

        }

    }