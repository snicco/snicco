<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\AuthTestCase;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Auth\Mail\ResetPasswordMail;
    use WPEmerge\Events\PendingMail;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Middleware\CsrfMiddleware;
    use WPEmerge\Support\Arr;

    class ForgotPasswordControllerTest extends AuthTestCase
    {


        private function postRequest(array $body, array $csrf)
        {

            $url = TestApp::url()->signedRoute('auth.forgot.password');

            $request = TestRequest::from('POST', $url);

            TestApp::container()->instance(Request::class, $request);

            $body = array_merge($body, [
                'csrf_name' => Arr::firstKey($csrf),
                'csrf_value' => Arr::firstEl($csrf),
            ]);

            return $request->withParsedBody($body);

        }

        private function getRequest() : TestRequest
        {

            $url = TestApp::url()->signedRoute('auth.forgot.password');

            $request = TestRequest::from('GET', $url);

            TestApp::container()->instance(Request::class, $request);

            return $request;
        }

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withAddedConfig('auth.features.password-resets', true);

            });

            // $this->afterApplicationCreated(function () {
            //     $this->withoutMiddleware('csrf');
            // });

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

            $this->withoutExceptionHandling();
            $this->mailFake();
            $token = $this->withCsrfToken();

            $url = $this->routeUrl();

            $calvin = $this->createAdmin();

            $response = $this->post($url, $token + ['login' => $calvin->user_login]);
            $response->assertRedirectToRoute('auth.forgot.password');

            $this->assertMailSent(ResetPasswordMail::class);

            // ApplicationEvent::assertDispatched(function (PendingMail $event) {
            //
            //     return $event->mail instanceof ResetPasswordMail;
            //
            // });


        }

        /** @test */
        public function a_password_reset_link_can_be_requested_by_email()
        {

            $calvin = $this->newAdmin();

            $this->newTestApp($this->config);
            $this->loadRoutes();

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest([
                'login' => $calvin->user_email,
            ], $csrf);

            ApplicationEvent::fake();

            $this->assertOutputContains('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $request->path());

            ApplicationEvent::assertDispatched(function (PendingMail $event) {

                return $event->mail instanceof ResetPasswordMail;

            });

        }

        /** @test */
        public function invalid_input_does_not_return_an_error_message_but_doesnt_send_an_email()
        {

            $calvin = $this->newAdmin();

            $this->newTestApp($this->config);
            $this->loadRoutes();

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest([
                'login' => 'bogus@web.de',
            ], $csrf);

            ApplicationEvent::fake();

            $this->assertOutputContains('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $request->path());

            ApplicationEvent::assertNotDispatched(PendingMail::class);


        }

        /** @test */
        public function the_mail_contains_a_magic_link_to_reset_the_password_and_is_sent_to_the_correct_user()
        {

            $calvin = $this->newAdmin();

            $this->newTestApp($this->config);
            $this->loadRoutes();

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest([
                'login' => $calvin->user_email,
            ], $csrf);

            ApplicationEvent::fake();

            $this->assertOutputContains('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $request->path());

            ApplicationEvent::assertDispatched(function (PendingMail $event) use ($calvin) {

                $mail = $event->mail;

                $this->assertStringContainsString('https://foo.com/auth/reset-password?expires', $mail->magic_link);
                $this->assertSame($calvin->user_email, $mail->to[0]->email);

                return $mail instanceof ResetPasswordMail;

            });


        }

    }