<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Illuminate\Support\Collection;
    use Tests\AuthTestCase;
    use Tests\helpers\HashesSessionIds;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Auth\Traits\GeneratesRecoveryCodes;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Encryptor;
    use WPEmerge\Session\SessionServiceProvider;

    class RecoveryCodeControllerTest extends AuthTestCase
    {

        /**
         * @var array
         */
        private $codes;

        /**
         * @var Encryptor
         */
        private $encryptor;

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

        private function createCodes() : array
        {

            return Collection::times(8, function () {

                return RecoveryCode::generate();

            })->all();

        }

        private function encryptCodes(array $codes) : string
        {

            return $this->encryptor->encrypt(json_encode($codes));

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
            $response->assertNoContent();

            $codes = get_user_meta($calvin->ID, 'two_factor_recovery_codes', true);
            $codes = json_decode($this->encryptor->decrypt($codes), true);

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
            $response1->assertNoContent();

            $response2 = $this->put($this->routePath(), $token, ['Accept' => 'application/json']);
            $response2->assertNoContent();


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