<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Closure;
    use WP_User;
    use WPEmerge\Auth\Events\Login;
    use WPEmerge\Auth\Contracts\LoginResponse;
    use WPEmerge\Auth\Responses\LogoutResponse;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Auth\Contracts\LoginViewResponse;
    use WPEmerge\Contracts\ResponsableInterface;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Url;

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

