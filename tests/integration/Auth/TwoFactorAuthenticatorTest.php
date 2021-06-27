<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Illuminate\Support\Collection;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreatePsr17Factories;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateRouteMatcher;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\Authenticators\TwoFactorAuthenticator;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Encryptor;
    use WPEmerge\Session\Session;

    class TwoFactorAuthenticatorTest extends IntegrationTest
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;
        use CreatePsr17Factories;
        use CreateRouteMatcher;
        use InteractsWithWordpress;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var TestRequest
         */
        private $request;

        /**
         * @var Delegate
         */
        private $next;

        /**
         * @var Encryptor
         */
        private $encryptor;


        protected function afterSetup()
        {

            $this->response_factory = $this->createResponseFactory();
            $this->request = TestRequest::from('POST', '/auth/login')
                                        ->withSession(new Session(new ArraySessionDriver(10)));

            $this->next = new Delegate(function (Request $request) {

                return $this->response_factory->html('Login-Test');

            });
            $this->encryptor = new Encryptor(TEST_APP_KEY);

        }

        private function newAuthenticator(bool $authenticate = false) : TwoFactorAuthenticator
        {

            $auth = new TwoFactorAuthenticator(new TestTwoFactorProvider($authenticate), $this->encryptor);
            $auth->setResponseFactory($this->response_factory);

            return $auth;

        }

        /** @test */
        public function if_the_session_has_no_challenged_user_the_authenticator_does_nothing()
        {

            $response = $this->newAuthenticator()->attempt($this->request, $this->next);
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame('Login-Test', $response->getBody()->__toString());

        }

        /** @test */
        public function a_challenged_user_without_2fa_enabled_can_bypass_the_authenticator () {

            $calvin = $this->newAdmin();
            $this->request->session()->put('2fa.challenged_user', $calvin->ID);

            // Calvin is challenged for some reason but he does not have 2Fa enabled.
            $response = $this->newAuthenticator()->attempt($this->request, $this->next);
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame('Login-Test', $response->getBody()->__toString());

        }

        /** @test */
        public function an_exception_is_thrown_if_a_challenged_user_cant_be_authenticated_with_a_one_time_code()
        {

            $calvin = $this->newAdmin();
            $this->request->session()->put('2fa.challenged_user', $calvin->ID);
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $request = $this->request->withParsedBody([
                'token' => '123456',
            ]);

            $this->expectException(FailedAuthenticationException::class);

            $this->newAuthenticator(false)->attempt($request, $this->next);

        }

        /** @test */
        public function the_user_is_logged_in_with_a_successful_login_code()
        {

            $calvin = $this->newAdmin();
            $this->request->session()->put('2fa.challenged_user', $calvin->ID);
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');


            $request = $this->request->withParsedBody([
                'token' => '123456',
            ]);


            /** @var SuccessfulLoginResponse $response */
            $response = $this->newAuthenticator(true )->attempt($request, $this->next);

            $this->assertInstanceOf(SuccessfulLoginResponse::class, $response);
            $this->assertSame($calvin->ID, $response->authenticatedUser()->ID);
            $this->assertFalse($response->rememberUser());
            $this->assertFalse($this->request->session()->has('2fa'));


        }

        /** @test */
        public function the_user_can_log_in_with_recovery_codes () {

            $calvin = $this->newAdmin();
            $this->request->session()->put('2fa.challenged_user', $calvin->ID);
            $codes = Collection::times(8, function () {

                return RecoveryCode::generate();

            })->all();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptor->encrypt(json_encode($codes)));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');


            $code = $codes[0];

            $request = $this->request->withParsedBody([
                'recovery-code' => $code,
            ]);

            $response = $this->newAuthenticator()->attempt($request, $this->next);
            $this->assertInstanceOf(SuccessfulLoginResponse::class, $response);
            $this->assertSame($calvin->ID, $response->authenticatedUser()->ID);
            $this->assertFalse($response->rememberUser());


            $request = $this->request->withParsedBody([
                'recovery-code' => 'bogus',
            ]);
            $request->session()->put('2fa.challenged_user', $calvin->ID);

            $this->expectException(FailedAuthenticationException::class);
            $this->newAuthenticator()->attempt($request, $this->next);

        }

        /** @test */
        public function the_recovery_code_is_swapped_on_successful_use () {


            $calvin = $this->newAdmin();
            $this->request->session()->put('2fa.challenged_user', $calvin->ID);
            $codes = Collection::times(8, function () {

                return RecoveryCode::generate();

            })->all();
            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encryptor->encrypt(json_encode($codes)));
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $code = $codes[0];

            $request = $this->request->withParsedBody([
                'recovery-code' => $code,
            ]);

            $response = $this->newAuthenticator()->attempt($request, $this->next);
            $this->assertInstanceOf(SuccessfulLoginResponse::class, $response);
            $this->assertSame($calvin->ID, $response->authenticatedUser()->ID);
            $this->assertFalse($response->rememberUser());

            $codes = get_user_meta($calvin->ID, 'two_factor_recovery_codes', true);
            $codes = json_decode($this->encryptor->decrypt($codes), true);

            $this->assertNotContains($code, $codes);


        }


    }


    class TestTwoFactorProvider implements TwoFactorAuthenticationProvider
    {

        /**
         * @var bool
         */
        private $authenticate;

        public function __construct(bool $authenticate)
        {

            $this->authenticate = $authenticate;
        }

        public function generateSecretKey($length = 16, $prefix = '') : string
        {
        }

        public function qrCodeUrl(string $company_name, string $user_identifier, string $secret)
        {
        }

        public function verify(string $secret, string $code) : bool
        {

            return $this->authenticate;
        }

        public function renderQrCode() : string
        {
        }



    }

