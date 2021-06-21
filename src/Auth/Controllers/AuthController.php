<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Events\Login;
    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Auth\Authenticator;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\Redirector;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Session\Example2;
    use WPEmerge\View\ViewFactory;

    class AuthController extends Controller
    {

        /**
         * @var Authenticator
         */
        private $authenticator;

        public function __construct(Authenticator $authenticator)
        {

            $this->authenticator = $authenticator;

        }

        public function create(Request $request, CsrfField $csrf) : ViewInterface
        {


            if ( $request->boolean('reauth')) {

                wp_clear_auth_cookie();

            }

            $view = $this->authenticator->view();

            return $this->view_factory->make('auth-parent')
                                ->with([
                                    'csrf_field' => $csrf->asHtml(),
                                    'post_url' => WP::loginUrl(),
                                    'redirect_to' => $request->input('redirect_to', admin_url()),
                                    'view_factory' => $this->view_factory,
                                    'view' => $view,
                                    'title' => 'Log-in | ' . WP::siteName(),
                                    'forgot_password' => $this->url->toRoute('auth.forgot.password'),
                                    'is_interim_login' => $request->boolean('interim-login')
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

            $remember = $request->has('remember_me');

            $request->session()->migrate(true );
            Login::dispatch([$user, $remember]);

            if ( $request->boolean('is_interim_login') ) {

                $request->session()->flash('interim_login_success', true );
                $html = $this->view_factory->render('auth-parent');
                return $this->response_factory->html($html);

            }

            return $this->redirectToDashboard($request);

        }

        public function destroy(Request $request, string $user_id, MagicLink $magic_link) : RedirectResponse
        {

            if ((int) $user_id !== WP::userId()) {

                throw new InvalidSignatureException();

            }

            $request->session()->invalidate();

            WP::logout();

            $magic_link->invalidate($request->fullUrl());

            $redirect_to = $request->query('redirect_to', $this->url->toRoute('home'));

            return $this->response_factory->redirect()->to($redirect_to)
                              ->withAddedHeader('Expires', 'Wed, 11 Jan 1984 06:00:00 GMT')
                              ->withAddedHeader('Cache-Control', 'no-cache, must-revalidate, max-age=0');

        }

        private function redirectToDashboard(Request $request) : RedirectResponse
        {

            if ( ! $request->has('redirect_to') ) {


                return $this->response_factory->redirect()->toRoute('dashboard');

            }

            return $this->response_factory->redirect()->to($request->input('redirect_to'));


        }


    }

