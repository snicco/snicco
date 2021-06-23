<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Closure;
    use SniccoAdapter\BaseContainerAdapter;
    use WPEmerge\Auth\Events\Login;
    use WPEmerge\Auth\Events\Logout;
    use WPEmerge\Auth\Responses\LoginResponse;
    use WPEmerge\Auth\Responses\LoginViewResponse;
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

    class AuthController extends Controller
    {

        /**
         * @var array
         */
        private $auth_config;

        /**
         * @var LoginViewResponse
         */
        private $login_view_response;

        public function __construct(LoginViewResponse $login_view_response, array $auth_config)
        {

            $this->auth_config = $auth_config;
            $this->login_view_response = $login_view_response;
        }

        public function create(Request $request) : ResponsableInterface
        {

            if ($request->boolean('reauth')) {

                $this->resetAuthSession($request->session());

            }

            $redirect_to = $request->input('redirect_to', admin_url());

            $request->session()->setIntendedUrl($redirect_to);

            return $this->login_view_response->withRequest($request)
                                             ->withAuthConfig($this->auth_config);

        }

        public function store(Request $request, Pipeline $auth_pipeline) : Response
        {


            $response = $auth_pipeline->send($request)
                                      ->through($this->auth_config['through'])
                                      ->then($this->handleAuthFailure());


            if ( $response instanceof LoginResponse ) {

                $remember = $response->rememberUser() && $this->auth_config['remember'] === true;

                Login::dispatch([$response->authenticatedUser(), $remember]);


            }

            return $response;


            try {

                $user = $this->authenticator->authenticate($request);

            }
            catch (FailedAuthenticationException $e) {

                return $this->response_factory->redirect()
                                              ->refresh()
                                              ->withErrors(['message' => $e->getMessage()])
                                              ->withInput($e->oldInput());

            }

            $remember = $request->boolean('remember_me');

            $session = $request->session();
            $session->put('auth.has_remember_token', $remember);
            $session->regenerate();

            Login::dispatch([$user, $remember]);

            if ($request->boolean('is_interim_login')) {

                $request->session()->flash('interim_login_success', true);
                $html = $this->view_factory->render('auth-parent');

                return $this->response_factory->html($html);

            }

            return $this->redirectToDashboard($request);

        }

        public function destroy(Request $request, string $user_id) : RedirectResponse
        {

            if ((int) $user_id !== WP::userId()) {

                throw new InvalidSignatureException();

            }

            Logout::dispatch([$request->session()]);

            $redirect_to = $request->query('redirect_to', $this->url->toRoute('home'));

            return $this->response_factory->redirect()->to($redirect_to)
                                          ->withAddedHeader('Expires', 'Wed, 11 Jan 1984 06:00:00 GMT')
                                          ->withAddedHeader('Cache-Control', 'no-cache, must-revalidate, max-age=0');

        }

        private function redirectToDashboard(Request $request) : RedirectResponse
        {

            if ( ! $request->has('redirect_to')) {


                return $this->response_factory->redirect()->toRoute('dashboard');

            }

            return $this->response_factory->redirect()->to($request->input('redirect_to'));


        }

        private function resetAuthSession(Session $session)
        {

            $session->invalidate();
            wp_clear_auth_cookie();


        }

        // None of our authenticators where able to authenticate the user.
        // Time to bail.
        private function handleAuthFailure() : Closure
        {

            return function () {

                throw new FailedAuthenticationException('Login failed', []);

            };

        }

    }

