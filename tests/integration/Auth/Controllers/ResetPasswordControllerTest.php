<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Support\Url;

    use function get_user_by;
    use function wp_check_password;

    class ResetPasswordControllerTest extends AuthTestCase
    {


        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withAddedConfig('auth.features.password-resets', true);

            });

            parent::setUp();

        }

        private function routeUrl(int $user_id)
        {

            $this->loadRoutes();

            return TestApp::url()
                          ->signedRoute('auth.reset.password', ['query' => ['id' => $user_id]], 300, true);

        }

        /** @test */
        public function the_endpoint_is_not_accessible_when_disabled_in_the_config()
        {

            $this->withOutConfig('auth.features.password-resets');

            $url = '/auth/reset-password';

            $this->get($url)->assertNullResponse();

        }

        /** @test */
        public function the_reset_password_view_can_be_rendered_with_a_valid_signature()
        {

            $calvin = $this->createAdmin();

            $response = $this->get($this->routeUrl($calvin->ID));
            $response->assertOk();
            $response->assertSee('Update password');
            $response->assertIsHtml();
            $response->assertSeeHtml(['/auth/reset-password', '_method', "value='PUT|"]);


        }

        /** @test */
        public function its_not_possible_to_tamper_with_the_id_of_the_signature_on_the_update_request()
        {


            $token = $this->withCsrfToken();
            $calvin = $this->createAdmin();

            $valid_url = $this->routeUrl($calvin->ID);
            $tampered_url = str_replace("id=$calvin->ID", "id=1", $valid_url);

            $this->put($tampered_url, $token)->assertForbidden();


        }

        /** @test */
        public function a_non_existing_user_id_cant_be_processed()
        {

            $this->withoutExceptionHandling();

            $token = $this->withCsrfToken();
            $url = $this->routeUrl(999);

            $response = $this->put($url, $token);

            $response->assertRedirect()->assertSessionHasErrors();

        }

        /** @test */
        public function passwords_must_be_at_least_12_characters()
        {


            $calvin = $this->createAdmin();
            $token = $this->withCsrfToken();

            $url = $this->routeUrl($calvin->ID);

            $response = $this->put($url, $token +
                [
                    'password' => str_repeat('a', 11),
                    'password_confirmation' => str_repeat('a', 11),
                ]
            );

            $response->assertRedirect()
                     ->assertSessionHasErrors(['password' => 'password must have a length between 12 and 64.'])
                     ->assertSessionDoesntHaveErrors('password_confirmation');

        }

        /** @test */
        public function passwords_must_be_more_less_than_64_characters()
        {

            $calvin = $this->createAdmin();
            $token = $this->withCsrfToken();

            $url = $this->routeUrl($calvin->ID);

            $response = $this->put($url, $token +
                [
                    'password' => str_repeat('a', 65),
                    'password_confirmation' => str_repeat('a', 65),
                ]
            );

            $response->assertRedirect()
                     ->assertSessionHasErrors(['password' => 'password must have a length between 12 and 64.'])
                     ->assertSessionDoesntHaveErrors('password_confirmation');


        }

        /** @test */
        public function passwords_must_be_identical()
        {

            $calvin = $this->createAdmin();
            $token = $this->withCsrfToken();

            $url = $this->routeUrl($calvin->ID);

            $response = $this->put($url, $token +
                [
                    'password' => str_repeat('a', 12),
                    'password_confirmation' => str_repeat('b', 12),
                ]
            );

            $response->assertRedirect()
                     ->assertSessionDoesntHaveErrors('password')
                     ->assertSessionHasErrors(['password_confirmation' => 'password_confirmation must be equal to password.']);


        }

        /** @test */
        public function weak_passwords_are_not_possible()
        {

            $calvin = $this->createAdmin();
            $token = $this->withCsrfToken();

            $url = $this->routeUrl($calvin->ID);

            $response = $this->put($url, $token +
                [
                    'password' => str_repeat('a', 12),
                    'password_confirmation' => str_repeat('a', 12),
                ]
            );

            $response->assertRedirect()
                     ->assertSessionDoesntHaveErrors('password_confirmation')
                     ->assertSessionHasErrors('password')
                     ->assertSessionHasErrors('reason')
                     ->assertSessionHasErrors('suggestions');

        }

        /** @test */
        public function a_password_can_be_reset()
        {

            $calvin = $this->createAdmin();
            $old_pass = $calvin->user_pass;
            $token = $this->withCsrfToken();

            $url = $this->routeUrl($calvin->ID);

            $this->followingRedirects();
            $response = $this->put($url, $token +
                [
                    'password' => $pw = 'asdasdcvqwe23442as$asd21!',
                    'password_confirmation' => $pw,
                ]
            );

            $response->assertOk()->assertSee(' You have successfully reset your password');

            $calvin = get_user_by('id', $calvin->ID);
            $new_pass = $calvin->user_pass;

            $this->assertNotSame($old_pass, $new_pass);
            $this->assertTrue(wp_check_password($pw, $new_pass));

            // Dont log the user in automatically
            $this->assertNotAuthenticated($calvin);

        }


    }