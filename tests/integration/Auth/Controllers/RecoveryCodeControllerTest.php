<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use BetterWP\Contracts\EncryptorInterface;
    use BetterWP\Routing\UrlGenerator;

    class RecoveryCodeControllerTest extends AuthTestCase
    {

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->with2Fa();
            });
            $this->afterApplicationCreated(function () {

                $this->codes = $this->createCodes();
                $this->encryptor = $this->app->resolve(EncryptorInterface::class);
            });

            parent::setUp();
        }

        private function routePath()
        {

            $this->loadRoutes();

            return $this->app->resolve(UrlGenerator::class)->toRoute('auth.2fa.recovery-codes');
        }

        /** @test */
        public function the_endpoint_cant_be_accessed_if_2fa_is_disabled()
        {

            $this->without2Fa();

            $this->actingAs($calvin = $this->createAdmin());

            $response = $this->get('/auth/two-factor/recovery-codes');
            $response->assertNullResponse();

        }

        /** @test */
        public function the_endpoint_cant_be_accessed_if_the_user_is_not_authenticated()
        {

            $response = $this->get($this->routePath());
            $response->assertRedirectPath('/auth/login', 302);

        }

        /** @test */
        public function the_endpoint_cant_be_accessed_if_the_auth_confirmation_expired()
        {

            $this->actingAs($calvin = $this->createAdmin());

            // confirmation duration is 10 seconds in the test config
            $this->travelIntoFuture(10);

            $response = $this->get($this->routePath());
            $response->assertRedirectToRoute('auth.confirm');

        }

        /** @test */
        public function all_recovery_codes_can_be_shown_for_a_user()
        {

            $this->actingAs($calvin = $this->createAdmin());

            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptCodes($this->codes));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->getJson($this->routePath());

            $response->assertExactJson($this->codes);


        }

        /** @test */
        public function recovery_codes_cant_be_retrieved_if_not_enabled_for_the_current_user()
        {

            $calvin = $this->createAdmin();
            $john = $this->createAdmin();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptCodes($this->codes));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $this->actingAs($john);

            $response = $this->getJson($this->routePath());

            $response->assertExactJson(['message' => 'Two factor authentication is not enabled.'])->assertStatus(409);

        }

        /** @test */
        public function recovery_codes_can_be_updated_for_a_user()
        {

            $token = $this->withCsrfToken();
            $calvin = $this->createAdmin();
            $this->actingAs($calvin);

            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptCodes($this->codes));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->put($this->routePath(), $token, ['Accept' => 'application/json']);
            $response->assertOk();

            $codes = get_user_meta($calvin->ID, 'two_factor_recovery_codes', true);
            $codes = json_decode($this->encryptor->decrypt($codes), true);

            $body = json_decode($response->getBody()->__toString(), true);

            $this->assertSame($codes, $body);
            $this->assertNotSame($this->codes, $codes);

        }

        /** @test */
        public function the_csrf_token_is_not_cleared_so_that_no_page_refresh_is_required_for_a_new_action()
        {

            $token = $this->withCsrfToken();
            $calvin = $this->createAdmin();
            $this->actingAs($calvin);
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptCodes($this->codes));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response1 = $this->put($this->routePath(), $token, ['Accept' => 'application/json']);
            $response1->assertOk();

            $response2 = $this->put($this->routePath(), $token, ['Accept' => 'application/json']);
            $response2->assertOk();


        }

        /** @test */
        public function recovery_codes_cant_be_updated_if_not_enabled_for_the_current_user()
        {

            $calvin = $this->createAdmin();
            $john = $this->createAdmin();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptCodes($this->codes));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $token = $this->withCsrfToken();
            $this->actingAs($john);

            $response = $this->put($this->routePath(), $token, ['Accept' => 'application/json']);

            $response->assertExactJson([
                'message' => 'Two factor authentication is not enabled.'
            ])->assertStatus(409);

        }

    }