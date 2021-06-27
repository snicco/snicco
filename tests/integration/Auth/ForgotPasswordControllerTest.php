<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\Mail\ResetPasswordMail;
    use WPEmerge\Events\PendingMail;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;

    class ForgotPasswordControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;

        protected $config = [
            'session' => [
                'enabled' => true,
                'driver' => 'array',
            ],
            'providers' => [
                SessionServiceProvider::class,
                AuthServiceProvider::class,
            ],
            'auth' => [
                'features' => [
                    'password-resets' => true
                ]
            ]
        ];

        private function postRequest(array $body, array $csrf)
        {

            $url = TestApp::url()->signedRoute('auth.forgot.password');

            $request = TestRequest::from('POST', $url );

            TestApp::container()->instance(Request::class, $request);

            $body = array_merge($body, [
                'csrf_name' => Arr::firstKey($csrf),
                'csrf_value' => Arr::firstEl($csrf),
            ]);

            return $request->withParsedBody($body);

        }

        private function getRequest( ) : TestRequest
        {

            $url = TestApp::url()->signedRoute('auth.forgot.password');

            $request = TestRequest::from('GET', $url );

            TestApp::container()->instance(Request::class, $request);

            return $request;
        }

        /** @test */
        public function the_route_cant_be_accessed_while_being_logged_in () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $url = TestApp::url()->signedRoute('auth.forgot.password');

            $request = TestRequest::from('GET', $url);
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean() );
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/wp-admin');


        }

        /** @test */
        public function the_forgot_password_view_can_be_rendered () {

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $request = $this->getRequest();

            $this->assertOutputContains('Request new password', $request);

        }

        /** @test */
        public function password_resets_can_be_disabled_via_the_config () {

            Arr::set($this->config, 'auth.features.password-resets', false);

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $request = TestRequest::from('GET', '/auth/forgot-password');
            $this->rebindRequest($request);
            $this->assertOutput('', $request);
            HeaderStack::assertHasNone();
        }

        /** @test */
        public function a_password_reset_link_can_be_requested_by_user_name () {

            $calvin = $this->newAdmin();

            $this->newTestApp($this->config);
            $this->loadRoutes();

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request  = $this->postRequest([
                'login' => $calvin->user_login
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
        public function a_password_reset_link_can_be_requested_by_email () {

            $calvin = $this->newAdmin();

            $this->newTestApp($this->config);
            $this->loadRoutes();

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request  = $this->postRequest([
                'login' => $calvin->user_email
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
        public function invalid_input_does_not_return_an_error_message_but_doesnt_send_an_email () {

            $calvin = $this->newAdmin();

            $this->newTestApp($this->config);
            $this->loadRoutes();

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request  = $this->postRequest([
                'login' => 'bogus@web.de'
            ], $csrf);

            ApplicationEvent::fake();

            $this->assertOutputContains('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $request->path());

            ApplicationEvent::assertNotDispatched(PendingMail::class);


        }

        /** @test */
        public function the_mail_contains_a_magic_link_to_reset_the_password_and_is_sent_to_the_correct_user () {

            $calvin = $this->newAdmin();

            $this->newTestApp($this->config);
            $this->loadRoutes();

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request  = $this->postRequest([
                'login' => $calvin->user_email
            ], $csrf);

            ApplicationEvent::fake();

            $this->assertOutputContains('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $request->path());

            ApplicationEvent::assertDispatched(function (PendingMail $event) use ( $calvin ) {

                $mail = $event->mail;

                $this->assertStringContainsString('https://foo.com/auth/reset-password?expires', $mail->magic_link);
                $this->assertSame($calvin->user_email, $mail->to[0]->email);

                return $mail instanceof ResetPasswordMail;

            });



        }



    }