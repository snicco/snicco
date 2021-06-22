<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Events\Login;
    use WPEmerge\Auth\Events\Logout;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Auth\Authenticator;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Session\Events\SessionRegenerated;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;

    class AuthController extends Controller
    {

        /**
         * @var Authenticator
         */
        private $authenticator;

        /**
         * @var array
         */
        private $auth_config;

        public function __construct(Authenticator $authenticator, array $auth_config)
        {

            $this->authenticator = $authenticator;

            $this->auth_config = $auth_config;
        }

        public function create(Request $request, CsrfField $csrf) : ViewInterface
        {

            if ($request->boolean('reauth')) {

                $this->resetAuthSession($request->session());

            }

            $view = $this->authenticator->view();

            return $this->view_factory->make('auth-parent')
                                      ->with([
                                          'csrf_field' => $csrf->asHtml(),
                                          'post_url' => WP::loginUrl(),
                                          'redirect_to' => $request->input('redirect_to', admin_url()),
                                          'view_factory' => $this->view_factory,
                                          'view' => $view,
                                          'title' => 'Log-in | '.WP::siteName(),
                                          'forgot_password' => $this->url->toRoute('auth.forgot.password'),
                                          'is_interim_login' => $request->boolean('interim-login'),
                                          'allow_remember' => $this->allowRememberMe(),
                                      ]);

        }

        public function store(Request $request) : Response
        {

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


            Login::dispatch([$user, $remember ]);

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

            $request->session()->invalidate();

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

        private function allowRememberMe() : bool
        {

            return Arr::get($this->auth_config, 'remember.enabled', true);

        }

    }

