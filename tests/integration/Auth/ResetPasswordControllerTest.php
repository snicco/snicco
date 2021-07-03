<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\AuthTestCase;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;

    class ResetPasswordControllerTest extends AuthTestCase
    {


        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withAddedConfig('auth.features.password-resets', true);

            });


            parent::setUp();

        }

        private function routeUrl(int $user_id)
        {

            $this->loadRoutes();

            return TestApp::url()->signedRoute('auth.reset.password', ['query' => ['id' => $user_id]], 300, true );

        }

        /** @test */
        public function the_endpoint_is_not_accessible_when_disabled_in_the_config()
        {

            $this->withOutConfig('auth.features.password-resets');

            $url = '/auth/reset-password';

            $this->get($url)->assertNullResponse();

        }

        /** @test */
        public function the_reset_password_view_can_be_rendered_with_a_valid_signature()
        {

            $calvin = $this->createAdmin();

            $response = $this->get($this->routeUrl($calvin->ID));
            $response->assertOk();
            $response->assertSee('Update password');
            $response->assertIsHtml();
            $response->assertSeeHtml(['/auth/reset-password', '_method_overwrite', "value='PUT'"]);


        }

        /** @test */
        public function its_not_possible_to_tamper_with_the_id_of_the_signature_on_the_update_request()
        {


            $token = $this->withCsrfToken();
            $calvin = $this->createAdmin();

            $valid_url = $this->routeUrl($calvin->ID);
            $tampered_url = str_replace("id=$calvin->ID", "id=1", $valid_url);

            $this->put($tampered_url, $token)->assertForbidden();


        }

        /** @test */
        public function a_non_existing_user_id_cant_be_processed_and_will_delete_access_to_the_route_for_the_user()
        {

            $this->withoutExceptionHandling();

            $token = $this->withCsrfToken();
            $url = $this->routeUrl(999);

            $response = $this->put($url, $token);

            //
            // $this->newTestApp($this->config);
            // $this->loadRoutes();
            //
            // $request = $this->postRequest(999, ['secret_csrf_name' => 'secret_csrf_value']);
            //
            // $this->assertOutput('', $request);
            // HeaderStack::assertHas('Location', '/auth/login');
            // HeaderStack::assertHasStatusCode(302);
            //
            // $this->assertNotEmpty(TestApp::session()->errors());

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
        public function passwords_must_be_identical()
        {

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
        public function weak_passwords_throw_an_exception()
        {

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
        public function a_password_can_be_reset()
        {

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