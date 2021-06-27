<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreatePsr17Factories;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateRouteMatcher;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\Authenticators\PasswordAuthenticator;
    use WPEmerge\Auth\Authenticators\RedirectIf2FaAuthenticable;
    use WPEmerge\Auth\Contracts\TwoFactorChallengeResponse;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Session;

    class RedirectIf2FaAuthenticableTest extends IntegrationTest
    {

        use CreatePsr17Factories;
        use AssertsResponse;
        use CreateRouteMatcher;
        use InteractsWithWordpress;
        use CreateUrlGenerator;
        use CreateRouteCollection;

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

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        protected function afterSetup()
        {

            $this->response_factory = $this->createResponseFactory();
            $this->authenticator = new RedirectIf2FaAuthenticable(new TestChallengeResponse($this->response_factory));
            $this->authenticator->setResponseFactory($this->response_factory);
            $this->request = TestRequest::from('POST', '/auth/login');
            $this->next = new Delegate(function (Request $request) {

                $p = new PasswordAuthenticator();
                $p->setResponseFactory($this->response_factory);

                return $p->attempt($request, new Delegate(function () {}));

            });

        }

        /** @test */
        public function any_non_login_response_is_returned_as_is () {

            $delegate = new Delegate(function (Request $request) {

                return $this->response_factory->null();

            });

            $response = $this->authenticator->attempt($this->request, $delegate);
            $this->assertInstanceOf(NullResponse::class, $response);

        }

        /** @test */
        public function a_successfully_authenticated_user_is_logged_in_if_he_doesnt_have_2fa_enabled () {

            $calvin = $this->newAdmin();

            $request = $this->request->withParsedBody([
                'pwd' => 'password',
                'log' => $calvin->user_login
            ]);

            /** @var SuccessfulLoginResponse $response */
            $response = $this->authenticator->attempt($request, $this->next);
            $this->assertInstanceOf(SuccessfulLoginResponse::class, $response);
            $this->assertSame($calvin->ID, $response->authenticatedUser()->ID);

        }

        /** @test */
        public function a_user_with_2fa_enabled_is_challenged () {

            $calvin = $this->newAdmin();

            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $request = $this->request->withParsedBody([
                'pwd' => 'password',
                'log' => $calvin->user_login,
                'remember_me' => '1',
            ])->withSession($s = new Session(new ArraySessionDriver(10)));

            $response = $this->authenticator->attempt($request, $this->next);
            $this->assertNotInstanceOf(SuccessfulLoginResponse::class, $response);
            $this->assertOutput('user challenged response', $response);
            $this->assertSame($calvin->ID, $s->challengedUser());
            $this->assertTrue($s->get('2fa.remember'));

        }

    }

    class TestChallengeResponse extends TwoFactorChallengeResponse {

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        public function __construct(ResponseFactory $response_factory)
        {

            $this->response_factory = $response_factory;
        }

        public function toResponsable()
        {
            return $this->response_factory->html('user challenged response');
        }

    }