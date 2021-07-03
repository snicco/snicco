<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\AuthTestCase;
    use WPEmerge\Auth\Authenticators\RedirectIf2FaAuthenticable;
    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Contracts\TwoFactorChallengeResponse;
    use WPEmerge\Auth\Middleware\AuthenticateSession;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;

    class RedirectIf2FaAuthenticableTest extends AuthTestCase
    {


        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->with2Fa();

                $this->withReplacedConfig('auth.through',
                    [
                        RedirectIf2FaAuthenticable::class,
                        TestAuthenticator::class,
                    ]
                );

            });

            $this->afterApplicationCreated(function () {

                $this->withoutMiddleware('csrf');
                $this->withoutMiddleware(AuthenticateSession::class);
                $this->instance(
                    TwoFactorChallengeResponse::class,
                    $this->app->resolve(TestChallengeResponse::class)
                );

            });

            parent::setUp();
        }

        /** @test */
        public function any_non_login_response_is_returned_as_is()
        {

            $response = $this->post('/auth/login');

            $response->assertRedirectToRoute('auth.login');
            $this->assertGuest();;

        }

        /** @test */
        public function a_successfully_authenticated_user_is_logged_in_if_he_doesnt_have_2fa_enabled()
        {

            $calvin = $this->createAdmin();
            $this->assertNotAuthenticated($calvin);


            $response = $this->post('/auth/login', [
                'login' => $calvin->user_login
            ]);

            $response->assertRedirectToRoute('dashboard');
            $this->assertAuthenticated($calvin);


        }

        /** @test */
        public function a_user_with_2fa_enabled_is_challenged()
        {

            $this->withoutExceptionHandling();

            $calvin = $this->createAdmin();
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');
            $this->assertNotAuthenticated($calvin);

            $response = $this->post('/auth/login', [
                'login' => $calvin->user_login
            ]);

            $response->assertSee('[test] Please enter your 2fa code.');
            $this->assertSame($calvin->ID, $response->session()->challengedUser());
            $this->assertNotAuthenticated($calvin);


        }

    }

    class TestAuthenticator extends Authenticator
    {

        use ResolvesUser;

        public function attempt(Request $request, $next) : Response
        {

            if ( ! $request->has('login') ) {
                return $this->response_factory->redirect()->toRoute('auth.login');
            }

            $user = $request->input('login');
            $user = $this->getUserByLogin($user);

            return $this->login($user);

        }

    }

    class TestChallengeResponse extends TwoFactorChallengeResponse
    {

        public function toResponsable()
        {
            return '[test] Please enter your 2fa code.';

        }

    }

