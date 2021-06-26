<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\helpers\CreatePsr17Factories;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateRouteMatcher;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\Authenticators\PasswordAuthenticator;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Http\Delegate;


    class PasswordAuthenticatorTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use CreatePsr17Factories;
        use CreateUrlGenerator;
        use CreateRouteCollection;
        use CreateRouteMatcher;

        /**
         * @var PasswordAuthenticator
         */
        private $authenticator;

        /**
         * @var Delegate
         */
        private $next;

        /**
         * @var TestRequest
         */
        private $request;

        protected function afterSetup()
        {

            $this->authenticator = new PasswordAuthenticator();
            $this->authenticator->setResponseFactory($this->createResponseFactory());
            $this->request = TestRequest::from('POST', '/auth/login');
            $this->next = new Delegate(function () {
            });

        }

        /** @test */
        public function missing_password_or_login_throws_an_exception()
        {

            try {

                $response = $this->authenticator->attempt($this->request, $this->next);
                $this->fail('Auth did not fail as expected.');
            }
            catch (\Throwable $e) {

                $this->assertInstanceOf(FailedAuthenticationException::class, $e);

            }

            try {

                $response = $this->authenticator->attempt($this->request->withParsedBody([
                    'pwd' => 'password',
                ]), $this->next);
                $this->fail('Auth did not fail as expected.');
            }
            catch (\Throwable $e) {

                $this->assertInstanceOf(FailedAuthenticationException::class, $e);

            }

            try {

                $response = $this->authenticator->attempt($this->request->withParsedBody([
                    'log' => 'login'
                ]), $this->next);
                $this->fail('Auth did not fail as expected.');
            }
            catch (FailedAuthenticationException $e) {

                $this->assertInstanceOf(FailedAuthenticationException::class, $e);

            }


        }

        /** @test */
        public function a_non_resolvable_user_throws_an_exception () {

            $GLOBALS['test']['failed_login'] = false;

            add_action('wp_login_failed', function () {
                $GLOBALS['test']['failed_login'] = true;
            });

            $request = $this->request->withParsedBody([
                'log' => 'calvin',
                'pwd' => 'password'
            ]);

            try {

                $this->authenticator->attempt($request, $this->next);


                $this->fail('Auth did not fail as expected.');

            } catch (FailedAuthenticationException $e ) {

                $this->assertTrue( $GLOBALS['test']['failed_login']);

            }

        }

        /** @test */
        public function a_user_can_login_with_valid_credentials () {

            $calvin = $this->newAdmin();

            $request = $this->request->withParsedBody([
                'log' => $calvin->user_login,
                'pwd' => 'password',
                'remember_me' => '1'
            ]);

            /** @var SuccessfulLoginResponse $response */
            $response = $this->authenticator->attempt($request, $this->next);

            $this->assertInstanceOf(SuccessfulLoginResponse::class, $response);
            $this->assertSame($calvin->ID, $response->authenticatedUser()->ID );
            $this->assertTrue($response->rememberUser());

        }

    }