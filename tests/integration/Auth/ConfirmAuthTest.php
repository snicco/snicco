<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Illuminate\Support\Carbon;
    use Tests\helpers\InteractsWithSessionDriver;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\SessionServiceProvider;

    class ConfirmAuthTest extends IntegrationTest
    {

        use InteractsWithSessionDriver;

        protected function afterSetup()
        {

            HeaderStack::reset();
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

        private function requestToProtectedRoute() : Request
        {

            $request = TestRequest::from('GET', 'auth-confirm/foo');
            $this->rebindRequest($request);

            return $this->withSessionCookie($request);

        }

        private function protectedUrl() : string
        {

            return $this->requestToProtectedRoute()->fullUrl();
        }

        /** @test */
        public function access_is_not_granted_to_routes_that_need_confirmation()
        {

            $this->newTestApp($this->config());

            $request = $this->requestToProtectedRoute();
            $this->registerRoutes();

            $this->assertOutputNotContains('Access to foo granted', $request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');


        }

        /** @test */
        public function a_valid_auth_token_that_is_not_expired_yet_grants_the_user_access()
        {

            $this->newTestApp($this->config());
            $this->writeTokenToSessionDriver(Carbon::now()->addSecond());

            $request = $this->requestToProtectedRoute();
            $this->registerRoutes();

            $this->assertOutputContains('Access to foo granted', $request);

            HeaderStack::assertHasStatusCode(200);

        }

        /** @test */
        public function an_expired_auth_token_does_not_grant_access()
        {

            $this->newTestApp($this->config());
            $this->writeTokenToSessionDriver(Carbon::now()->subSecond());

            $request = $this->requestToProtectedRoute();
            $this->registerRoutes();

            $this->assertOutputNotContains('Access to foo granted', $request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

        }

        /** @test */
        public function a_failed_auth_check_invalidates_the_session()
        {

            $this->newTestApp($this->config());
            $this->writeTokenToSessionDriver(Carbon::now()->subSecond());
            $session = $this->getSession();

            $request = $this->requestToProtectedRoute();
            $this->registerRoutes();

            $this->assertOutputNotContains('Access to foo granted', $request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

            $new_id = $session->getId();

            $this->assertNotSame($this->testSessionId(), $new_id);


        }

        /** @test */
        public function the_session_is_not_invalidated_when_an_email_was_already_sent_out()
        {

            $this->newTestApp($this->config());
            $this->writeToDriver(['auth' => [
                'confirm' => [
                    'email' => [
                        'count' => 1
                    ]
                ]
            ]]);

            $request = $this->requestToProtectedRoute();
            $this->registerRoutes();

            $this->assertOutputNotContains('Access to foo granted', $request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

            $this->assertSame($this->testSessionId(), $this->getSession()->getId());


        }

        /** @test */
        public function the_intended_url_is_saved_to_the_session_on_failure()
        {

            $this->newTestApp($this->config());

            $request = $this->requestToProtectedRoute();
            $this->registerRoutes();

            $this->assertOutputNotContains('Access to foo granted', $request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

            $new_session = $this->getSession();

            $this->assertSame($new_session->getIntendedUrl(), $this->protectedUrl());


        }

        /** @test */
        public function a_failing_check_is_redirected_to_the_correct_url()
        {

            $this->newTestApp($this->config());

            $request = $this->requestToProtectedRoute();
            $this->registerRoutes();
            $expected_url = TestApp::routeUrl('auth.confirm.show', [], true, false);

            $this->assertOutputNotContains('Access to foo granted', $request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', $expected_url);


        }

    }

