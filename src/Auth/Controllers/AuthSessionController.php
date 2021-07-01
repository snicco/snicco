<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Closure;
    use WP_User;
    use WPEmerge\Auth\Events\Login;
    use WPEmerge\Auth\Events\Logout;
    use WPEmerge\Auth\Contracts\LoginResponse;
    use WPEmerge\Auth\Responses\LogoutResponse;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Auth\Contracts\LoginViewResponse;
    use WPEmerge\Contracts\ResponsableInterface;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;

    class AuthSessionController extends Controller
    {

        /**
         * @var array
         */
        private $auth_config;

        public function __construct(array $auth_config)
        {

            $this->auth_config = $auth_config;
        }

        public function create(Request $request, LoginViewResponse $view_response) : ResponsableInterface
        {

            if ($request->boolean('reauth')) {

                $this->resetAuthSession($request->session());

            }

            $redirect_to = $request->query('redirect_to', $this->url->toRoute('dashboard'));

            $request->session()->setIntendedUrl($redirect_to);

            return $view_response->withRequest($request)
                                 ->withAuthConfig($this->auth_config);

        }

        public function store(Request $request, Pipeline $auth_pipeline, LoginResponse $login_response)
        {

            $response = $auth_pipeline->send($request)
                                      ->through($this->auth_config['through'])
                                      ->then($this->handleAuthFailure());

            if ($response instanceof SuccessfulLoginResponse) {

                $remember = $response->rememberUser() && $this->auth_config['remember']['enabled'] === true;
                $user = $response->authenticatedUser();

                return $this->handleLogin($user, $remember, $login_response, $request);

            }

            return $response;


        }

        public function destroy(Request $request, string $user_id) : Response
        {

            if ((int) $user_id !== WP::userId()) {

                throw new InvalidSignatureException();

            }

            $redirect_to = $request->query('redirect_to', $this->url->toRoute('home'));

            $response = $this->response_factory->redirect()->to($redirect_to);

            return new LogoutResponse($response);

        }

        private function resetAuthSession(Session $session)
        {

            $session->invalidate();
            wp_clear_auth_cookie();


        }

        private function handleLogin(WP_User $user, bool $remember, LoginResponse $login_response, Request $request)
        {

            $session = $request->session();
            $session->setUserId($user->ID);
            $session->put('auth.has_remember_token', $remember);
            $session->confirmAuthUntil(Arr::get($this->auth_config, 'confirmation.duration', 0));
            $session->regenerate();

            Login::dispatch([$user, $remember]);

            // We are inside an iframe and just need to close it with js.
            if ($request->boolean('is_interim_login')) {

                $request->session()->flash('interim_login_success', true);

                return $this->response_factory->view('auth-layout');

            }

            return $login_response->forRequest($request)->forUser($user);


        }

        // None of our authenticators where able to authenticate the user.
        // Time to bail.
        private function handleAuthFailure() : Closure
        {

            return function (Request $request) {

                throw new FailedAuthenticationException('Login failed' , $request, null , [] );

            };

        }

    }

