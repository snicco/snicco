<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Illuminate\Support\Carbon;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Session\Session;

    class ConfirmAuthTest extends IntegrationTest
    {

        protected function afterSetup()
        {
            HeaderStack::reset();
        }

        private function config()
        {

            $config = TEST_CONFIG;

            $config['providers'] = [SessionServiceProvider::class];
            $config['session'] = [
                'enabled' => true,
                'driver'=> 'array'
            ];

            return $config;

        }

        private function getSession () :Session {

            return TestApp::resolve(Session::class);

        }

        private function requestToProtectedRoute() :Request {

            $request = TestRequest::from('GET', 'auth-confirm/foo');

            $cookie = 'wp_mvc_session=' . $this->getSession()->getId();

            return $request->withAddedHeader('Cookie', $cookie );

        }

        private function protectedUrl() :string {
            return $this->requestToProtectedRoute()->getFullUrl();
        }

        /** @test */
        public function access_is_not_granted_to_routes_that_need_confirmation () {

            $this->newTestApp($this->config());

            $this->assertOutputNotContains('Access to foo granted', $this->requestToProtectedRoute());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');


        }

        /** @test */
        public function a_valid_auth_token_that_is_not_expired_yet_grants_the_user_access () {

            $this->newTestApp($this->config());

            $this->getSession()->put('auth.confirm.until', Carbon::now()->addSecond()->getTimestamp());

            $this->assertOutputContains('Access to foo granted', $this->requestToProtectedRoute());

            HeaderStack::assertHasStatusCode(200);

        }

        /** @test */
        public function an_expired_auth_token_does_not_grant_access () {

            $this->newTestApp($this->config());

            $this->getSession()->put('auth.confirm.until', Carbon::now()->subSecond()->getTimestamp());

            $this->assertOutputNotContains('Access to foo granted', $this->requestToProtectedRoute());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

        }

        /** @test */
        public function a_failed_auth_check_invalidates_the_session () {

            $this->newTestApp($this->config());

            $old_session = $this->getSession();
            $old_id = $old_session->getId();

            $this->assertOutputNotContains('Access to foo granted', $this->requestToProtectedRoute());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

            $new_session = $this->getSession();
            $new_id = $new_session->getId();

            $this->assertNotSame($old_id, $new_id);

            $this->assertSame($old_session, $new_session);


        }

        /** @test */
        public function the_session_is_not_invalidated_when_an_email_was_already_sent_out () {

            $this->newTestApp($this->config());

            $old_session = $this->getSession();
            $old_session->put('auth.confirm.email.count', 1 );
            $old_id = $old_session->getId();

            $this->assertOutputNotContains('Access to foo granted', $this->requestToProtectedRoute());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

            $new_session = $this->getSession();
            $new_id = $new_session->getId();

            $this->assertSame($old_id, $new_id);
            $this->assertSame($old_session, $new_session);


        }

        /** @test */
        public function the_intended_url_is_saved_to_the_session_on_failure () {

            $this->newTestApp($this->config());

            $this->assertOutputNotContains('Access to foo granted', $this->requestToProtectedRoute());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

            $new_session = $this->getSession();

            $this->assertSame($new_session->getIntendedUrl(), $this->protectedUrl());


        }

        /** @test */
        public function a_failing_check_is_redirected_to_the_correct_url () {

            $this->newTestApp($this->config());

            $expected_url = TestApp::routeUrl('auth.confirm.show', [], true, false);

            $this->assertOutputNotContains('Access to foo granted', $this->requestToProtectedRoute());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $expected_url);



        }

    }

