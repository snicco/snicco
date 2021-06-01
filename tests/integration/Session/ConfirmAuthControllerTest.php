<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Illuminate\Support\Carbon;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Controllers\ConfirmAuthController;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Session\SessionStore;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;

    class ConfirmAuthControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;

        /**
         * @var array;
         */
        private $mail;

        protected function afterSetup()
        {
            add_filter('pre_wp_mail',[$this, 'catchMail'], 10, 3);

        }

        protected function beforeTearDown()
        {

            remove_all_filters('pre_wp_mail', 10);

        }

        private function config()
        {

            $config = TEST_CONFIG;

            $config['providers'] = [SessionServiceProvider::class];
            $config['session'] = [
                'enabled' => true,
                'driver' => 'array',
            ];


            return $config;

        }

        private function getSession() : SessionStore
        {

            return TestApp::resolve(SessionStore::class);

        }

        private function controllerUrl() : string
        {

            return '/auth/confirm';

        }

        private function getRequest(string $url = null) : Request
        {

            return TestRequest::from('GET', $url??$this->controllerUrl());

        }

        private function postRequest(string $email, array $csrf)
        {

            $request = TestRequest::from('POST', $this->controllerUrl());

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
         * @see ConfirmAuthController::show()
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

            $this->assertOutputContains('confirmation email', $this->getRequest());

            HeaderStack::assertHasStatusCode(200);

            $this->logout($calvin);


        }

        /** @test */
        public function the_route_cant_be_accessed_without_being_logged_in()
        {

            $this->newTestApp($this->config());

            $this->assertOutputNotContains('confirmation email', $this->getRequest());

            $expected_redirect_url = '/wp-login.php?redirect_to=https%3A%2F%2Ffoo.com%2Fauth%2Fconfirm&reauth=1';

            HeaderStack::assertHasStatusCode(401);
            HeaderStack::assertHas('Location', $expected_redirect_url);

        }

        /** @test */
        public function the_url_to_the_send_method_is_included()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());

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

            $this->assertOutputContains('csrf_name', $this->getRequest());
            $this->assertOutputContains('csrf_value', $this->getRequest());

            HeaderStack::assertHasStatusCode(200);

            $this->logout($calvin);


        }

        /** @test */
        public function the_intended_url_is_reflashed()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());

            $session = $this->getSession();

            // flashed from previous request
            $session->flash('auth.confirm.intended_url', 'foobar.com');
            $session->save();

            $this->assertOutputContains('confirmation email', $this->getRequest());
            HeaderStack::assertHasStatusCode(200);

            // Session was saved by middleware
            $this->assertSame('foobar.com', $session->get('auth.confirm.intended_url'));

            $this->logout($calvin);


        }

        /** @test */
        public function the_total_attempts_to_input_a_correct_user_email_is_three_by_default_before_a_user_gets_logged_out()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());
            $session = $this->getSession();

            $this->assertNull($session->get('auth.confirm.attempts'));

            $this->assertOutputContains('confirmation email', $this->getRequest());
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::reset();
            $this->assertSame(1, $session->get('auth.confirm.attempts'));

            $this->assertOutputContains('confirmation email', $this->getRequest());
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::reset();
            $this->assertSame(2, $session->get('auth.confirm.attempts'));

            $this->assertOutputContains('confirmation email', $this->getRequest());
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::reset();
            $this->assertSame(3, $session->get('auth.confirm.attempts'));

            $this->assertOutputNotContains('confirmation email', $this->getRequest());
            HeaderStack::assertHasStatusCode(429);
            HeaderStack::assertHas('Location', WP::loginUrl());

            $this->logout($calvin);

        }

        /** @test */
        public function the_session_is_invalidated_when_the_max_attempts_are_reached()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());

            $session = $this->getSession();
            $session->put('foo', 'bar');
            $session->put('auth.confirm.attempts', 3);
            $id = $session->getId();

            $this->assertOutputNotContains('confirmation email', $this->getRequest());
            HeaderStack::assertHasStatusCode(429);
            HeaderStack::assertHas('Location', WP::loginUrl());

            $this->assertNotSame($id, $session->getId());
            $this->assertFalse($session->has('foo'));

            $this->logout($calvin);

        }

        /** @test */
        public function the_user_is_logged_out_completely_if_max_attempts_are_reached()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());

            $this->getSession()->put('auth.confirm.attempts', 3);
            $this->assertUserLoggedIn($calvin);

            $this->assertOutputNotContains('confirmation email', $this->getRequest());
            HeaderStack::assertHasStatusCode(429);
            HeaderStack::assertHas('Location', WP::loginUrl());

            $this->assertUserLoggedOut();


        }

        /**
         *
         *
         *
         * @send
         * @see ConfirmAuthController::send()
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

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest('foo@bar@web.de', $csrf);

            $this->assertOutputNotContains('confirmation email', $request);
            HeaderStack::assertHasStatusCode(404);
            HeaderStack::assertHas('Location', $this->controllerUrl());


        }

        /** @test */
        public function a_non_existing_user_email_will_redirect_back()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest('bogus@web.de', $csrf);

            $this->assertOutputNotContains('confirmation email', $request);
            HeaderStack::assertHasStatusCode(404);
            HeaderStack::assertHas('Location', $this->controllerUrl());


        }

        /** @test */
        public function the_intended_url_is_reflashed_on_failure () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());

            $session = $this->getSession();

            // flashed from previous request
            $session->flash('auth.confirm.intended_url', 'foobar.com');
            $session->save();

            $session->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest('bogus@web.de', $csrf);

            $this->assertOutputNotContains('confirmation email', $request);
            HeaderStack::assertHasStatusCode(404);
            HeaderStack::assertHas('Location', $this->controllerUrl());

            $this->assertSame('foobar.com', $session->get('auth.confirm.intended_url'));

        }

        /** @test */
        public function the_error_notice_is_rendered () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $this->newTestApp($this->config());

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $request = $this->postRequest($email = 'bogus@web.de', $csrf);

            $this->assertOutputNotContains( $email, $request);
            HeaderStack::assertHasStatusCode(404);
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
         * @see ConfirmAuthController::send()
         *
         *
         * PASSING CHECKS
         *
         *
         */

        /** @test */
        public function the_user_is_redirect_back_with_200_code_on_success () {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de'
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest('c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $this->controllerUrl());

        }

        /** @test */
        public function the_user_cant_request_unlimited_email_sending () {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de'
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            // 4th one will throw the user in jail.
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(429);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();

            // After 10 min he is still in jail
            Carbon::setTestNow(Carbon::now()->addMinutes(10));
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(429);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();
            Carbon::setTestNow();

            // After 15 min he cant try again
            Carbon::setTestNow(Carbon::now()->addMinutes(15));
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest('c@web.de', $csrf);
            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(429);
            HeaderStack::assertHas('Location', $this->controllerUrl());
            HeaderStack::reset();



        }

        /** @test */
        public function the_success_message_is_rendered () {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de'
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest( $email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $redirected_url = $this->controllerUrl());

            $get_request = $this->getRequest($redirected_url);

            $output = $this->runKernel($get_request);

            $this->assertStringContainsString('Email sent ', $output, 'Confirmation message not found in the view.');
            $this->assertStringContainsString('in 5', $output, 'Expiration time of 5 min not found in the view.');

        }

        /** @test */
        public function the_submit_form_is_not_rendered () {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de'
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest( $email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $redirected_url = $this->controllerUrl());

            $get_request = $this->getRequest($redirected_url);

            $output = $this->runKernel($get_request);

            $this->assertStringNotContainsString('id="send"', $output, 'Form was rendered when it should not.');

        }

        /** @test */
        public function the_resend_email_form_is_rendered () {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de'
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());

            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $post_request = $this->postRequest( $email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $redirected_url = $this->controllerUrl());

            $get_request = $this->getRequest($redirected_url);

            $output = $this->runKernel($get_request);

            $this->assertStringContainsString('id="resend-email"', $output, 'id [resend-email] was rendered not when it should.');

        }

        /** @test */
        public function the_attempts_are_removed_from_the_session () {

            $calvin = $this->newAdmin([
                'user_email' => 'c@web.de'
            ]);
            $this->login($calvin);

            $this->newTestApp($this->config());

            $session = $this->getSession();
            $session->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $session->put('auth.confirm.attempts', 2);

            $post_request = $this->postRequest( $email = 'c@web.de', $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $this->controllerUrl());

            $this->assertFalse($session->has('auth.confirm.attempts'));
        }

        /** @test */
        public function an_email_is_sent_to_the_user () {

            $calvin = $this->newAdmin([
                'user_email' => $email = 'c@web.de',
                'first_name' => 'calvin'
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());
            $this->getSession()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $post_request = $this->postRequest( $email, $csrf);

            $this->assertOutput('', $post_request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', $this->controllerUrl());

            $this->assertSame($email,$this->mail['to']);
            $this->assertStringContainsString('link',$this->mail['subject']);
            $this->assertContains('Content-Type: text/html; charset=UTF-8', $this->mail['headers']);
            $this->assertStringContainsString('Hi calvin', $this->mail['message']);
            $this->assertStringContainsString('in 5', $this->mail['message']);


        }

        /** @test */
        public function the_signed_url_included_in_the_email_works () {

            $calvin = $this->newAdmin([
                'user_email' => $email = 'c@web.de',
                'first_name' => 'calvin'
            ]);
            $this->login($calvin);
            $this->newTestApp($this->config());

            $session = $this->getSession();
            $session->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);
            $session->flash('auth.confirm.intended_url', 'foobar.com/intended');
            $post_request = $this->postRequest( $email, $csrf);

            $this->runKernel($post_request);
            HeaderStack::reset();

            $mail_content = $this->mail['message'];
            $url = trim(Str::between($mail_content, 'href="', '"'));
            $url = html_entity_decode($url);

            $get_request = TestRequest::fromFullUrl('GET', $url);
            $this->assertOutput('', $get_request);

            HeaderStack::assertHasStatusCode(200);
            HeaderStack::assertHas('Location', 'foobar.com/intended');

            $this->assertUserLoggedIn($calvin);

        }

        public function catchMail($null ,$attributes) : bool
        {

            $this->mail = $attributes;
            return true;


        }


    }