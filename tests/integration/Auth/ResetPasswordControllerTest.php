<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;
    use WPEmerge\Validation\ValidationServiceProvider;

    class ResetPasswordControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;

        protected $config = [
            'app_key' => TEST_APP_KEY,
            'session' => [
                'enabled' => true,
                'driver' => 'array',
            ],
            'providers' => [
                SessionServiceProvider::class,
                AuthServiceProvider::class,
                ValidationServiceProvider::class,
            ],
        ];

        private function postRequest(int $user_id, array $csrf, array $payload = [])
        {

            $url = TestApp::url()
                          ->signedRoute('auth.reset.password', ['query' => ['id' => $user_id]], 300, true);

            $request = TestRequest::fromFullUrl('POST', $url);

            TestApp::session()->put('csrf', $csrf);

            TestApp::container()->instance(Request::class, $request);

            return $request->withParsedBody([
                    'csrf_name' => Arr::firstKey($csrf),
                    'csrf_value' => Arr::firstEl($csrf),
                ] + $payload);

        }

        private function getRequest(int $user_id) : TestRequest
        {

            $url = TestApp::url()
                          ->signedRoute('auth.reset.password', ['query' => ['id' => $user_id]], 300, true);

            $request = TestRequest::fromFullUrl('GET', $url);

            TestApp::container()->instance(Request::class, $request);

            return $request;

        }

        /** @test */
        public function the_reset_password_view_can_be_rendered()
        {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $output = $this->runKernel($request = $this->getRequest($calvin->ID));

            $this->assertStringContainsString('Update password', $output, 'Text [update password] not found in the view.');
            $this->assertStringContainsString($request->path(), $output, 'Post url not found in the view.');

        }

        /** @test */
        public function its_not_possible_to_tamper_with_the_id_of_the_signature_on_the_post_request()
        {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = $this->postRequest($calvin->ID, ['secret_csrf_name' => 'secret_csrf_value']);

            $uri = $request->getUri();
            $tampered_query = str_replace(strval($calvin->ID), '2', $uri->getQuery());
            $new = $uri->withQuery($tampered_query);

            $this->expectException(InvalidSignatureException::class);
            $this->runKernel($request->withUri($new));


        }

        /** @test */
        public function a_non_existing_user_id_cant_be_processed()
        {

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $request = $this->postRequest(999, ['secret_csrf_name' => 'secret_csrf_value']);

            $this->assertOutput('', $request);
            HeaderStack::assertHas('Location', '/auth/login');
            HeaderStack::assertHasStatusCode(302);

            $this->assertNotEmpty(TestApp::session()->errors());

        }

        /** @test */
        public function passwords_must_be_at_least_12_characters()
        {

            $this->newTestApp($this->config, true);

            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = $this->postRequest($calvin->ID, ['secret_csrf_name' => 'secret_csrf_value'], [
                'password' => str_repeat('a', 11),
                'password_confirmation' => str_repeat('a', 11),
            ]);

            TestApp::session()->setPreviousUrl($request->path());

            $this->assertOutput('', $request);
            HeaderStack::assertHas('Location', $request->path());
            HeaderStack::assertHasStatusCode(302);

            $errors = TestApp::session()->errors();

            $this->assertTrue($errors->has('password'));
            $this->assertStringContainsString('password must have a length between 12 and 64.', $errors->first('password'));


        }

        /** @test */
        public function passwords_must_be_more_than_characters()
        {

            $this->newTestApp($this->config, true);

            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = $this->postRequest($calvin->ID, ['secret_csrf_name' => 'secret_csrf_value'], [
                'password' => str_repeat('a', 65),
                'password_confirmation' => str_repeat('a', 65),
            ]);

            TestApp::session()->setPreviousUrl($request->path());

            $this->assertOutput('', $request);
            HeaderStack::assertHas('Location', $request->path());
            HeaderStack::assertHasStatusCode(302);

            $errors = TestApp::session()->errors();

            $this->assertTrue($errors->has('password'));
            $this->assertStringContainsString('password must have a length between 12 and 64.', $errors->first('password'));


        }

        /** @test */
        public function passwords_must_be_identical () {

            $this->newTestApp($this->config, true);

            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = $this->postRequest($calvin->ID, ['secret_csrf_name' => 'secret_csrf_value'], [
                'password' => str_repeat('a', 12),
                'password_confirmation' => str_repeat('a', 13),
            ]);

            TestApp::session()->setPreviousUrl($request->path());

            $this->assertOutput('', $request);
            HeaderStack::assertHas('Location', $request->path());
            HeaderStack::assertHasStatusCode(302);

            $errors = TestApp::session()->errors();

            $this->assertTrue($errors->has('password_confirmation'));
            $this->assertStringContainsString('password_confirmation must be equal to password.', $errors->first('password_confirmation'));

        }

        /** @test */
        public function weak_passwords_throw_an_exception () {

            $this->newTestApp($this->config, true);

            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = $this->postRequest($calvin->ID, ['secret_csrf_name' => 'secret_csrf_value'], [
                'password' => str_repeat('a', 12),
                'password_confirmation' => str_repeat('a', 12),
            ]);

            TestApp::session()->setPreviousUrl($request->path());

            $this->assertOutput('', $request);
            HeaderStack::assertHas('Location', $request->path());
            HeaderStack::assertHasStatusCode(302);

            $errors = TestApp::session()->errors();

            $this->assertTrue($errors->has('password'));
            $this->assertTrue($errors->has('reason'));
            $this->assertTrue($errors->has('suggestions'));

        }

        /** @test */
        public function a_password_can_be_reset () {

            $this->newTestApp($this->config, true);

            $this->loadRoutes();
            $calvin = $this->newAdmin();
            $old_pass = $calvin->user_pass;

            $request = $this->postRequest($calvin->ID, ['secret_csrf_name' => 'secret_csrf_value'], [
                'password' => $pw = 'asdasdcvqwe23442as$asd21!',
                'password_confirmation' => $pw,
            ]);

            $this->assertOutput('', $request);
            HeaderStack::assertHas('Location', $request->path());
            HeaderStack::assertHasStatusCode(302);
            $this->assertTrue(TestApp::session()->get('_password_reset.success'));

            $calvin = get_user_by('id', $calvin->ID);
            $new_pass = $calvin->user_pass;

            $this->assertNotSame($old_pass, $new_pass);
            $this->assertTrue(wp_check_password($pw, $new_pass));

        }



    }