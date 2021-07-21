<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use Tests\stubs\TestApp;
    use Snicco\Auth\Mail\ResetPasswordMail;

    class ForgotPasswordControllerTest extends AuthTestCase
    {


        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withAddedConfig('auth.features.password-resets', true);

            });


            parent::setUp();
        }

        private function routeUrl()
        {

            $this->loadRoutes();

            return TestApp::url()->signedRoute('auth.forgot.password');
        }

        /** @test */
        public function the_route_cant_be_accessed_while_being_logged_in()
        {

            $this->actingAs($this->createAdmin());

            $this->get($this->routeUrl())->assertRedirectToRoute('dashboard');

        }

        /** @test */
        public function the_forgot_password_view_can_be_rendered()
        {

            $this->get($this->routeUrl())
                 ->assertSee('Request new password')
                 ->assertOk();


        }

        /** @test */
        public function the_endpoint_is_not_accessible_when_disabled_in_the_config()
        {

            $this->withOutConfig('auth.features.password-resets');

            $url = '/auth/forgot-password';

            $this->get($url)->assertNullResponse();


        }

        /** @test */
        public function a_password_reset_link_can_be_requested_by_user_name()
        {

            $this->mailFake();
            $token = $this->withCsrfToken();

            $url = $this->routeUrl();

            $calvin = $this->createAdmin();

            $response = $this->post($url, $token + ['login' => $calvin->user_login]);
            $response->assertRedirectToRoute('auth.forgot.password');

            $mail = $this->assertMailSent(ResetPasswordMail::class);

            $expected_link = TestApp::url()->toRoute('auth.reset.password', [], true, true);

            $mail->assertTo($calvin)
                 ->assertView('password-forgot-email')
                ->assertSee("$expected_link?expires=");



        }

        /** @test */
        public function a_password_reset_link_can_be_requested_by_user_email()
        {

            $this->mailFake();
            $token = $this->withCsrfToken();

            $url = $this->routeUrl();

            $calvin = $this->createAdmin();

            $response = $this->post($url, $token + ['login' => $calvin->user_email]);
            $response->assertRedirectToRoute('auth.forgot.password');

            $mail = $this->assertMailSent(ResetPasswordMail::class);

            $expected_link = TestApp::url()->toRoute('auth.reset.password', [], true, true);

            $mail->assertTo($calvin)
                 ->assertView('password-forgot-email')
                 ->assertSee("$expected_link?expires=");


        }

        /** @test */
        public function invalid_input_does_not_return_an_error_message_but_doesnt_send_an_email()
        {

            $this->mailFake();
            $token = $this->withCsrfToken();

            $response = $this->post($this->routeUrl(), $token + ['login' => 'bogus@web.de']);

            $response->assertRedirectToRoute('auth.forgot.password');
            $this->assertMailNotSent(ResetPasswordMail::class);


        }


    }