<?php


    declare(strict_types = 1);


    namespace WPEmerge\EnhancedAuth\Controllers;

    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\EnhancedAuth\Authenticator;
    use WPEmerge\EnhancedAuth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
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
                                    'post_url' => wp_login_url(),
                                    'redirect_to' => $request->input('redirect_to', admin_url()),
                                    'view_factory' => $view_factory,
                                    'view' => $view,
                                    'title' => 'Log-in | ' . WP::siteName(),
                                    'forgot_password' => $this->generator->toRoute('forgot.password.show')
                                ]);

        }

        public function __invoke(Request $request, ResponseFactory $response_factory)
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

        protected function redirectResponse(Request $request) : RedirectResponse
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