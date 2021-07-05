<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Controllers;

    use Closure;
    use WP_User;
    use BetterWP\Auth\Events\Login;
    use BetterWP\Auth\Contracts\LoginResponse;
    use BetterWP\Auth\Responses\LogoutResponse;
    use BetterWP\Auth\Responses\SuccessfulLoginResponse;
    use BetterWP\Auth\Contracts\LoginViewResponse;
    use BetterWP\Contracts\ResponsableInterface;
    use BetterWP\Auth\Exceptions\FailedAuthenticationException;
    use BetterWP\ExceptionHandling\Exceptions\InvalidSignatureException;
    use BetterWP\Support\WP;
    use BetterWP\Http\Controller;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Routing\Pipeline;
    use BetterWP\Session\Session;
    use BetterWP\Support\Arr;
    use BetterWP\Support\Url;

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

            $request->session()->setIntendedUrl(
                $this->parseRedirect($request)
            );

            return $view_response->withRequest($request)
                                 ->withAuthConfig($this->auth_config);

        }

        public function store(Request $request, Pipeline $auth_pipeline, LoginResponse $login_response)
        {

            $response = $auth_pipeline->send($request)
                                      ->through($this->auth_config['through'])
                                      ->then($this->handleAuthFailure());

            if ($response instanceof SuccessfulLoginResponse) {

                $remember = $response->rememberUser() && $this->auth_config['features']['remember_me'] > 0;
                $user = $response->authenticatedUser();

                return $this->handleLogin($user, $remember, $login_response, $request);

            }

            return $response;


        }

        public function destroy(Request $request, int $user_id) : Response
        {

            if ($user_id !== WP::userId()) {

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
            wp_set_auth_cookie($user->ID, $remember, true);
            wp_set_current_user($user->ID);

            Login::dispatch([$user, $remember]);

            // We are inside an iframe and just need to close it with js.
            if ($request->boolean('is_interim_login')) {

                return $this->response_factory->view('auth-interim-login-success');

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

        private function parseRedirect(Request $request) : string
        {

            $redirect_to = $request->query('redirect_to', $this->url->toRoute('dashboard'));

            // redirect_to will be urldecoded which means that we can not be sure that a potential
            // query string will still be urlencoded correctly.
            // Since we have no control over where plugins and core call wp_login_url() its best to rebuild the query.
            $parts = parse_url($redirect_to);

            if ( isset($parts['query'])) {

                return Url::rebuildQuery($redirect_to);

            }

            return $redirect_to;


        }

    }

