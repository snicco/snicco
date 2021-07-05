<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use BetterWP\Auth\Mail\MagicLinkLoginMail;

    class LoginMagicLinkControllerTest extends AuthTestCase
    {

        protected function setUp() : void
        {
            $this->afterLoadingConfig(function () {

                $this->withReplacedConfig('auth.authenticator', 'email');

            });

            $this->afterApplicationCreated(function () {
                $this->withoutMiddleware('csrf');
            });
            parent::setUp();

        }

        /** @test */
        public function the_route_cant_be_accessed_if_the_authenticator_is_not_email () {

            $this->withReplacedConfig('auth.authenticator', 'password');

            $response = $this->post('/auth/login/create-magic-link');

            $response->assertNullResponse();

        }

        /** @test */
        public function the_route_cant_be_accessed_if_authenticated () {


            $this->actingAs($this->createAdmin());

            $response = $this->post('/auth/login/create-magic-link')->assertRedirectToRoute('dashboard');


        }

        /** @test */
        public function no_email_is_sent_for_invalid_user_login () {

            $this->withoutExceptionHandling();
            $this->mailFake();

            $response = $this->post('/auth/login/create-magic-link', ['login' => 'bogus']);
            $response->assertRedirect('/auth/login');
            $response->assertSessionHas('login.link.processed');

            $this->assertMailNotSent(MagicLinkLoginMail::class);


        }

        /** @test */
        public function a_login_email_is_sent_for_valid_user_login () {

            $this->mailFake();

            $calvin = $this->createAdmin();

            $response = $this->post('/auth/login/create-magic-link', ['login' => $calvin->user_login]);
            $response->assertRedirect('/auth/login');
            $response->assertSessionHas('login.link.processed');

            $mail = $this->assertMailSent(MagicLinkLoginMail::class);
            $mail->assertTo($calvin);
            $mail->assertSee('/auth/login/magic-link?expires=');
            $mail->assertSee('/auth/login/magic-link?expires=');
            $mail->assertSee("user_id=$calvin->ID");

        }

    }
