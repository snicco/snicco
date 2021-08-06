<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Snicco\Auth\Mail\ConfirmAuthMail;
    use Tests\AuthTestCase;

    class AuthConfirmationEmailControllerTest extends AuthTestCase
    {

        private string $endpoint = '/auth/confirm/magic-link';

        /** @test */
        public function the_endpoint_exists()
        {

            $this->withoutExceptionHandling();
            $this->post($this->endpoint)->assertNotNullResponse();

        }

        /** @test */
        public function the_endpoint_cant_be_accessed_if_not_authenticated()
        {

            $token = $this->withCsrfToken();
            $response = $this->post($this->endpoint, $token);
            $response->assertRedirectPath('/auth/login');

        }

        /** @test */
        public function the_endpoint_cant_be_accessed_if_auth_is_confirmed()
        {

            $this->actingAs($this->createAdmin());
            $token = $this->withCsrfToken();
            $response = $this->post($this->endpoint, $token);
            $response->assertRedirectToRoute('dashboard');

        }

        /** @test */
        public function a_confirmation_email_can_be_requested()
        {

            $this->mailFake();

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            $token = $this->withCsrfToken();

            $response = $this->post($this->endpoint, $token, ['referer' => 'https://foobar.com/auth/confirm']);
            $response->assertRedirect('/auth/confirm');
            $response->assertSessionHas('auth.confirm.email.sent', function ($value) {
                return $value === true;
            });
            $response->assertSessionHas('auth.confirm.email.cool_off', function ($value) {
                return $value === 15;
            });

            $mail = $this->assertMailSent(ConfirmAuthMail::class);
            $mail->assertTo($calvin);
            $mail->assertViewHas(['magic_link']);
            $mail->assertSee('/auth/confirm/magic-link?expires=');

        }

        /** @test */
        public function a_confirmation_email_can_be_requested_JSON()
        {

            $this->mailFake();

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            $token = $this->withCsrfToken();

            $response = $this->post($this->endpoint, $token, ['Accept' => 'application/json']);
            $response->assertStatus(204);

            $mail = $this->assertMailSent(ConfirmAuthMail::class);
            $mail->assertTo($calvin);

        }

        /** @test */
        public function users_cant_request_unlimited_emails()
        {

            $this->withoutExceptionHandling();

            $this->mailFake();

            $this->authenticateAndUnconfirm($calvin = $this->createAdmin());
            $token = $this->withCsrfToken();

            $response = $this->post($this->endpoint, $token, ['referer' => 'https://foobar.com/auth/confirm']);
            $response->assertRedirect('/auth/confirm');

            $this->assertMailSent(ConfirmAuthMail::class)
                 ->assertTo($calvin);

            $this->clearSentMails();

            $token = $this->withCsrfToken();
            $response = $this->post($this->endpoint, $token, ['referer' => 'https://foobar.com/auth/confirm']);
            $response->assertRedirect('/auth/confirm')
                     ->assertSessionHasErrors('auth.confirm.email.message')
                     ->assertSessionHas('auth.confirm.email.next');

            $this->assertMailNotSent(ConfirmAuthMail::class);

            $this->clearSentMails();

            $this->travelIntoFuture(16);
            $token = $this->withCsrfToken();
            $response = $this->post($this->endpoint, $token, ['referer' => 'https://foobar.com/auth/confirm']);
            $response->assertRedirect('/auth/confirm')
                     ->assertSessionHasNoErrors();

            $this->assertMailSent(ConfirmAuthMail::class)
                 ->assertTo($calvin);

        }

    }