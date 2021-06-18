<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Illuminate\Support\Carbon;
    use Tests\helpers\HashesSessionIds;
    use Tests\helpers\InteractsWithSessionDriver;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Auth\Controllers\AuthConfirmationController;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;

    class AuthConfirmationControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use InteractsWithSessionDriver;
        use HashesSessionIds;

        /**
         * @var array;
         */
        private $mail;

        protected function afterSetup()
        {

            add_filter('pre_wp_mail', [$this, 'catchMail'], 10, 3);
        }

        protected function beforeTearDown()
        {

            remove_all_filters('pre_wp_mail', 10);

        }

        private function config()
        {

            $config = TEST_CONFIG;

            $config['providers'] = [SessionServiceProvider::class, AuthServiceProvider::class];
            $config['session'] = [
                'enabled' => true,
                'driver' => 'array',
            ];

            return $config;

        }

        private function controllerUrl() : string
        {

            return '/auth/confirm';

        }

        private function getRequest(string $url = null) : Request
        {

            $request = TestRequest::from('GET', $url ?? $this->controllerUrl());

            TestApp::container()->instance(Request::class, $request);

            return $request;

        }

        private function postRequest(string $email, array $csrf)
        {

            $request = TestRequest::from('POST', $this->controllerUrl());

            TestApp::container()->instance(Request::class, $request);

            return $request->withParsedBody([
                'email' => $email,
                'csrf_name' => Arr::firstKey($csrf),
                'csrf_value' => Arr::firstEl($csrf),
            ]);

        }


        /**
         *
         *
         *
         * @show
         * @see AuthConfirmationController::create()
         *
         *
         *
         */

        /** @test */
        public function the_email_confirmation_view_is_rendered()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();
            $this->registerAndRunApiRoutes();

            $this->assertOutputContains('confirmation email', $this->getRequest());

            HeaderStack::assertHasStatusCode(200);

            $this->logout($calvin);


        }

        /** @test */
        public function the_route_cant_be_accessed_without_being_logged_in()
        {

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->assertOutputNotContains('confirmation email', $this->getRequest());

            $expected_redirect_url = '/login?redirect_to=%2Fauth%2Fconfirm';

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $expected_redirect_url);

        }

        /** @test */
        public function the_url_to_the_send_method_is_included()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $expected = '/auth/confirm';

            $this->assertOutputContains($expected, $this->getRequest());

            HeaderStack::assertHasStatusCode(200);

            $this->logout($calvin);


        }

        /** @test */
        public function the_csrf_field_is_included_in_the_send_method_is_included_in_the_view()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->assertOutputContains('csrf_name', $this->getRequest());
            $this->assertOutputContains('csrf_value', $this->getRequest());

            HeaderStack::assertHasStatusCode(200);

            $this->logout($calvin);


        }

        /** @test */
        public function the_total_attempts_to_input_a_correct_user_email_is_three_by_default_before_a_user_gets_logged_out()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'calvin@xyz.de',
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->writeToDriver([
                'csrf' => $csrf = ['csrf_secret_name' => 'csrf_secret_value'],
                'auth' => [
                    'confirm' => [
                        'attempts' => 2
                    ]
                ]
            ]);

            $post_request = $this->postRequest('bogus@web.de', $csrf);
            $post_request = $this->withSessionCookie($post_request);

            // email failed but user is still logged in
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/auth/confirm');
            HeaderStack::reset();
            $this->assertUserLoggedIn($calvin);

            $this->getSession()->put('csrf', $csrf);
            $post_request = $this->postRequest('bogus@web.de', $csrf);
            $post_request = $this->withSessionCookie($post_request);

            // this failed attempt will log the user out.
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/login?redirect_to=%2Fauth%2Fconfirm');
            HeaderStack::reset();

            $this->assertUserLoggedOut();

        }

        /** @test */
        public function the_session_is_invalidated_when_the_max_attempts_are_reached()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->writeToDriver([
                'csrf' => $csrf = ['csrf_secret_name' => 'csrf_secret_value'],
                'auth' => [
                    'confirm' => [
                        'attempts' => 3
                    ]
                ],
                'foo' => 'bar'
            ]);

            $this->assertNotSame('', $this->readFromDriver($this->hashedSessionId()));

            $post_request = $this->withSessionCookie($this->postRequest('bogus@web.de', $csrf));

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/login?redirect_to=%2Fauth%2Fconfirm');


            $this->assertSame('', $this->readFromDriver($this->testSessionId()));

            $this->logout($calvin);

        }

        /**
         *
         *
         *
         * @send
         * @see AuthConfirmationController::send()
         *
         *
         * FAILED CHECKS
         *
         *
         */

        /** @test */
        public function submitting_a_post_request_with_an_invalid_email_will_redirect_the_user()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest('foo@bar@web.de', $csrf);

            $this->assertOutputNotContains('confirmation email', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $this->controllerUrl());


        }

        /** @test */
        public function a_non_existing_user_email_will_redirect_back()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest('bogus@web.de', $csrf);

            $this->assertOutputNotContains('confirmation email', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $this->controllerUrl());


        }


        /** @test */
        public function the_error_notice_is_rendered()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest($email = 'bogus@web.de', $csrf);

            $this->assertOutputNotContains($email, $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $url = $this->controllerUrl());

            $request = $this->getRequest($url);

            $output = $this->runKernel($request);

            $this->assertStringContainsString('Error:', $output, 'Error message not found in the view.');
            $this->assertStringContainsString($email, $output, 'old email input not found in the view.');


        }

        /**
         *
         *
         *
         * @send
         * @see AuthConfirmationController::send()
         *
         *
         * PASSING CHECKS
         *
         *
         */

        /** @test */
        public function the_user_is_redirect_back_with_200_code_on_success()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest('c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $this->controllerUrl());

        }

        /** @test */
        public function the_user_cant_request_unlimited_email_sending()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            // 4th one will throw the user in jail.
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            // After 10 min he is still in jail
            Carbon::setTestNow(Carbon::now()->addMinutes(10));
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();
            Carbon::setTestNow();

            // After 15 min he cant try again
            Carbon::setTestNow(Carbon::now()->addMinutes(15));
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();


        }

        /** @test */
        public function the_success_message_is_rendered()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest($email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $redirected_url = $this->controllerUrl());

            $get_request = $this->getRequest($redirected_url);

            $output = $this->runKernel($get_request);

            $this->assertStringContainsString('Email sent ', $output, 'Confirmation message not found in the view.');
            $this->assertStringContainsString('in 5', $output, 'Expiration time of 5 min not found in the view.');

        }

        /** @test */
        public function the_submit_form_is_not_rendered()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest($email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $redirected_url = $this->controllerUrl());

            $get_request = $this->getRequest($redirected_url);

            $output = $this->runKernel($get_request);

            $this->assertStringNotContainsString('id="send"', $output, 'Form was rendered when it should not.');

        }

        /** @test */
        public function the_resend_email_form_is_rendered()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest($email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $redirected_url = $this->controllerUrl());

            $get_request = $this->getRequest($redirected_url);

            $output = $this->runKernel($get_request);

            $this->assertStringContainsString('id="resend-email"', $output, 'id [resend-email] was rendered not when it should.');

        }

        /** @test */
        public function the_submit_from_does_not_get_rendered_once_at_least_one_email_was_sent_correctly()
        {

            $calvin = $this->newAdmin([
                'user_email' => $email = 'c@web.de',
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();
            $get_request = $this->getRequest();

            $this->assertOutputContains('id="send"', $get_request, 'id [send] was not rendered when it should.');

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest($email, $csrf);
            $this->assertOutput('', $post_request);

            $output = $this->runKernel($get_request);
            $this->assertStringNotContainsString('id="send"', $output, 'id [send] was not rendered when it should.');
            $this->assertStringContainsString($email, $output, 'the email address was not rendered when it should.');

            $output = $this->runKernel($get_request);
            $this->assertStringNotContainsString('id="send"', $output, 'id [send] was not rendered when it should.');
            $this->assertStringContainsString($email, $output, 'the email address was not rendered when it should.');


        }

        /** @test */
        public function the_success_message_only_shows_when_an_email_was_sent_on_the_prev_request()
        {

            $calvin = $this->newAdmin([
                'user_email' => $email = 'c@web.de',
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $this->triggerEmailSending($email);

            $get_request = $this->getRequest();

            $output = $this->runKernel($get_request);
            $this->assertStringContainsString('Email sent successfully', $output, 'the message [Email sent successfully] was not rendered when it should.');

            $output = $this->runKernel($get_request);
            $this->assertStringNotContainsString('Email sent successfully', $output, 'The success message was rendered when it should not.');
            $this->assertStringContainsString('already sent', $output, 'The already sent message was rendered when it should not.');

        }

        /** @test */
        public function the_attempts_are_removed_from_the_session()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $session = $this->getSession();
            $session->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $session->put('auth.confirm.attempts', 2);

            $post_request = $this->postRequest($email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $this->controllerUrl());

            $this->assertFalse($session->has('auth.confirm.attempts'));
        }

        /** @test */
        public function an_email_is_send_to_the_user()
        {

            $calvin = $this->newAdmin([
                'user_email' => $email = 'c@web.de',
                'first_name' => 'calvin',
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest($email, $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $this->controllerUrl());

            $this->assertSame([$email], $this->mail['to']);
            $this->assertStringContainsString('link', $this->mail['subject']);
            $this->assertContains('Content-Type: text/html; charset=UTF-8', $this->mail['headers']);
            $this->assertStringContainsString('Hi calvin', $this->mail['message']);
            $this->assertStringContainsString('in 5', $this->mail['message']);


        }

        /** @test */
        public function the_signed_url_included_in_the_email_works()
        {

            $calvin = $this->newAdmin([
                'user_email' => $email = 'c@web.de',
                'first_name' => 'calvin',
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $session = $this->getSession();
            $session->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $session->setIntendedUrl('/intended-page');
            $post_request = $this->postRequest($email, $csrf);

            $this->runKernel($post_request);
            HeaderStack::reset();

            $mail_content = $this->mail['message'];
            $url = trim(Str::between($mail_content, 'href="', '"'));
            $url = html_entity_decode($url);

            $get_request = TestRequest::fromFullUrl('GET', $url);
            $this->assertOutput('', $get_request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/intended-page');

            $this->assertUserLoggedIn($calvin);

        }

        /** @test */
        public function users_with_valid_session_token_cant_access_the_auth_confirm_routes_for_get_requests()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
                'first_name' => 'calvin',
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $session = $this->getSession();
            $session->put('auth.confirm.until', Carbon::now()->addMinutes(30)->getTimestamp());

            $get_request = $this->getRequest();

            $this->assertOutput('', $get_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/wp-admin');

            Carbon::setTestNow();

        }

        /** @test */
        public function users_with_valid_session_token_cant_access_the_auth_confirm_routes_for_post_requests()
        {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de',
                'first_name' => 'calvin',
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->registerAndRunApiRoutes();

            $session = $this->getSession();
            $session->put('auth.confirm.until', Carbon::now()->addMinutes(30)->getTimestamp());

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest('c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/wp-admin');
            HeaderStack::reset();
            Carbon::setTestNow();


        }

        public function catchMail($null, $attributes) : bool
        {

            $this->mail = $attributes;

            return true;


        }

        private function triggerEmailSending(string $email)
        {

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest($email, $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

        }

    }