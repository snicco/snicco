<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use BetterWP\Contracts\EncryptorInterface;

    class TwoFactorAuthPreferenceControllerTest extends AuthTestCase
    {

        private $endpoint = '/auth/two-factor/preferences';

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->with2Fa();
            });

            $this->afterApplicationCreated(function () {
                $this->withHeader('Accept', 'application/json');
                $this->encryptor = $this->app->resolve(EncryptorInterface::class);
            });

            parent::setUp();
        }

        /** @test */
        public function the_endpoint_is_not_accessible_with_2fa_disabled()
        {

            $this->without2Fa();
            $this->post($this->endpoint)->assertNullResponse();

        }

        /** @test */
        public function the_endpoint_is_not_accessible_if_not_authenticated()
        {

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
        public function an_error_is_returned_if_2fa_is_already_enabled_for_the_user()
        {

            $this->actingAs($calvin = $this->createAdmin());
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->post($this->endpoint, [], ['Accept' => 'application/json']);

            $response->assertStatus(409)
                     ->assertIsJson()
                     ->assertExactJson([
                         'message' => 'Two-Factor authentication is already enabled.',
                     ]);

        }

        /** @test */
        public function two_factor_authentication_can_be_enabled () {

            $this->actingAs($calvin = $this->createAdmin());

            $response = $this->post($this->endpoint);
            $response->assertOk();

            $body = $response->body();

            $codes_in_body = json_decode($body, true);
            $this->assertCount(8, $codes_in_body);
            $codes_in_db = $this->getUserRecoveryCodes($calvin);
            $this->assertSame($codes_in_db, $codes_in_body);

            $this->assertIsString($this->getUserSecret($calvin));


        }

        /** @test */
        public function two_factor_authentication_can_not_be_disabled_for_user_who_dont_have_it_enabled () {

            $this->actingAs($calvin = $this->createAdmin());

            $response = $this->delete($this->endpoint);
            $response->assertStatus(409)->assertExactJson([
                'message' => 'Two-Factor authentication is not enabled.'
            ]);

        }

        /** @test */
        public function two_factor_authentication_can_be_disabled () {

            $this->actingAs($calvin = $this->createAdmin());
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptCodes($this->createCodes()));

            $response = $this->delete($this->endpoint);
            $response->assertNoContent();

            $this->assertEmpty($this->getUserSecret($calvin));
            $this->assertEmpty($this->getUserRecoveryCodes($calvin));


        }

    }