<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Auth\Authenticator;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Redirector;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\View\ViewFactory;

    class AuthController
    {

        /**
         * @var AbstractRedirector
         */
        private $redirector;
        /**
         * @var Authenticator
         */
        private $authenticator;
        /**
         * @var UrlGenerator
         */
        private $generator;

        public function __construct(AbstractRedirector $redirector, Authenticator $authenticator, UrlGenerator $generator)
        {

            $this->redirector = $redirector;
            $this->authenticator = $authenticator;
            $this->generator = $generator;
        }

        public function create(Request $request, ViewFactory $view_factory, CsrfField $csrf) : ViewInterface
        {

            $view = $this->authenticator->view();

            return $view_factory->make('auth-parent')
                                ->with([
                                    'csrf_field' => $csrf->asHtml(),
                                    'post_url' => WP::loginUrl(),
                                    'redirect_to' => $request->input('redirect_to', admin_url()),
                                    'view_factory' => $view_factory,
                                    'view' => $view,
                                    'title' => 'Log-in | ' . WP::siteName(),
                                    'forgot_password' => $this->generator->toRoute('auth.forgot.password')
                                ]);

        }

        public function store(Request $request, ResponseFactory $response_factory) : RedirectResponse
        {

            try {

                $user = $this->authenticator->authenticate($request);

            }
            catch (FailedAuthenticationException $e) {

                return $response_factory->redirect()
                                        ->back()
                                        ->withErrors(['message' => $e->getMessage()])
                                        ->withInput($e->oldInput());

            }

            $remember = $request->has('remember_me');

            wp_set_auth_cookie($user->ID, $remember, true);

            do_action('wp_login', $user->user_login, $user);

            return $this->redirectResponse($request);

        }

        public function destroy(Request $request, string $user_id, MagicLink $magic_link) : RedirectResponse
        {

            if ((int) $user_id !== WP::userId()) {

                throw new InvalidSignatureException();

            }

            $request->session()->invalidate();

            WP::logout();

            $magic_link->invalidate($request->fullUrl());

            $redirect_to = $request->query('redirect_to', $this->generator->toRoute('home'));

            return $this->redirector->to($redirect_to)
                              ->withAddedHeader('Expires', 'Wed, 11 Jan 1984 06:00:00 GMT')
                              ->withAddedHeader('Cache-Control', 'no-cache, must-revalidate, max-age=0');

        }

        private function redirectResponse(Request $request) : RedirectResponse
        {

            $location = WP::adminUrl();

            if ($request->has('redirect_to')) {

                $location = $request->input('redirect_to');

            }

            $response = $this->redirector->intended($request, $location);

            $url = $response->getHeaderLine('Location');

            $parsed = parse_url($url);

            if ( ! isset($parsed['host']) || $request->getUri()->getHost() !== $parsed['host']) {

                return $this->redirector->secure(WP::adminUrl());

            }

            return $response;


        }

    }