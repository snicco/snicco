<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Controllers;

    use Closure;
    use Snicco\Auth\Contracts\LoginResponse;
    use Snicco\Auth\Contracts\LoginViewResponse;
    use Snicco\Auth\Events\Login;
    use Snicco\Auth\Exceptions\FailedAuthenticationException;
    use Snicco\Auth\Responses\LogoutResponse;
    use Snicco\Auth\Responses\SuccessfulLoginResponse;
    use Snicco\Contracts\ResponsableInterface;
    use Snicco\ExceptionHandling\Exceptions\InvalidSignatureException;
    use Snicco\Http\Controller;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;
    use Snicco\Routing\Pipeline;
    use Snicco\Session\Session;
    use Snicco\Support\Arr;
    use Snicco\Support\Url;
    use Snicco\Support\WP;
    use WP_User;

    class AuthSessionController extends Controller
    {

        private array $auth_config;

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

            return $view_response->forRequest($request)
                                 ->withAuthConfig($this->auth_config);

        }

        public function store(Request $request, Pipeline $auth_pipeline, LoginResponse $login_response)
        {

            $response = $auth_pipeline->send($request)
                                      ->through($this->auth_config['through'])
                                      ->then($this->handleAuthFailure());

            if ($response instanceof SuccessfulLoginResponse) {

                $remember = $response->rememberUser() && $this->allowRememberMe();
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
            // Since we have no control over where plugins and core call wp_login_url() it's best to rebuild the query.
            $parts = parse_url($redirect_to);

            if ( isset($parts['query'])) {

                return Url::rebuildQuery($redirect_to);

            }

            return $redirect_to;


        }

        private function allowRememberMe() : bool
        {
            return  Arr::get($this->auth_config, 'features.remember_me', false ) === true;
        }

    }

