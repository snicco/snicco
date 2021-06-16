<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Illuminate\Support\Carbon;
    use Tests\helpers\InteractsWithSessionDriver;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\ExceptionHandling\Exceptions\NotFoundException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Session\SessionServiceProvider;

    class ConfirmAuthMagicLinkControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use InteractsWithSessionDriver;

        private function createSignedUrl(int $user_id, $intended = '') : string
        {

            /** @var UrlGenerator $url */
            $url = TestApp::resolve(UrlGenerator::class);

            return $url->signedRoute('auth.confirm.magic-login',
                [
                    'user_id' => $user_id,
                    'query' => [
                        'intended' => $intended,
                    ],
                ], 300 , true );

        }

        private function newApp()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                    'driver' => 'array',
                ],
                'providers' => [
                    SessionServiceProvider::class,
                    AuthServiceProvider::class
                ],
                'exception_handling' => [
                    'enable' => false,
                ],
            ]);
        }

        /** @test */
        public function the_route_cant_be_accessed_without_valid_signature()
        {

            $this->expectException(InvalidSignatureException::class);

            $this->newApp();

            $this->withoutExceptionHandling();

            $this->registerRoutes();
            $url = TestApp::routeUrl('auth.confirm.magic-login', ['user_id' => 1]);


            $this->runKernel(TestRequest::from('GET', $url));


        }

        /** @test */
        public function a_404_exception_is_created_for_user_ids_that_dont_exist()
        {

            $this->expectException(NotFoundException::class);

            $this->newApp();
            $this->withoutExceptionHandling();

            $this->registerRoutes();
            $url = $this->createSignedUrl(999);

            $this->runKernel(TestRequest::fromFullUrl('GET', $url));

        }

        /** @test */
        public function users_get_redirected_to_the_intended_url_from_the_query_string()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '/settings');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', '/settings');
            HeaderStack::assertHasStatusCode(302);

            $this->logout($calvin);

        }

        /** @test */
        public function a_user_gets_redirected_to_the_intended_url_from_the_session_if_not_present_in_query_string()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $this->writeToDriver([
                '_url' => [
                    'intended' => '/settings'
                ]
            ]);

            $request = TestRequest::fromFullUrl('GET', $url);
            $request = $this->withSessionCookie($request);

            $this->seeKernelOutput('', $request);
            HeaderStack::assertHas('Location', '/settings');
            HeaderStack::assertHasStatusCode(302);

            $this->logout($calvin);

        }

        /** @test */
        public function a_user_gets_redirected_to_the_admin_dashboard_if_no_intended_url_can_be_generated()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->logout($calvin);

        }

        /** @test */
        public function the_auth_confirm_token_gets_saved_to_the_session () {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $this->seeKernelOutput('', TestRequest::from('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertSame(
                Carbon::now()->addMinutes(180)->getTimestamp(),
                TestApp::session()->get('auth.confirm.until')
            );

            $this->logout($calvin);

        }

        /** @test */
        public function a_user_that_is_not_logged_in_gets_logged_in () {

            $calvin = $this->newAdmin();

            $this->assertUserLoggedOut();

            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $this->seeKernelOutput('', TestRequest::from('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertUserLoggedIn($calvin->ID);

            $this->logout($calvin);


        }

        /** @test */
        public function the_current_session_is_migrated()
        {

            $calvin = $this->newAdmin();

            $this->newApp();

            $this->writeToDriver([
                'foo' => 'bar'
            ]);

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $request = TestRequest::from('GET', $url);
            $request = $this->withSessionCookie($request);


            $this->seeKernelOutput('', $request);
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertSame('', TestApp::session()->getDriver()->read($this->testSessionId()));

            $this->logout();

        }

        /** @test */
        public function the_wp_auth_cookie_is_set () {

            $calvin = $this->newAdmin();

            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            add_action('set_auth_cookie', function () {
                $GLOBALS['test']['auth_cookie'] = true;
            });

            $this->seeKernelOutput('', TestRequest::from('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertTrue($GLOBALS['test']['auth_cookie']);

            $this->logout();

        }


    }
