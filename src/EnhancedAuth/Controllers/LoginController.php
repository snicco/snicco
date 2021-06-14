<?php


    declare(strict_types = 1);


    namespace WPEmerge\EnhancedAuth\Controllers;

    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\EnhancedAuth\Authenticator;
    use WPEmerge\EnhancedAuth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;

    class LoginController
    {

        /**
         * @var AbstractRedirector
         */
        private $redirector;

        public function __construct( AbstractRedirector $redirector)
        {
            $this->redirector = $redirector;
        }

        public function __invoke(Request $request, Authenticator $authenticator, ResponseFactory $response_factory)
        {

            try {

                $user = $authenticator->authenticate($request);

            } catch (FailedAuthenticationException $e ) {

                return $response_factory->redirect()
                                        ->back()
                                        ->withErrors(['message' => $e->getMessage()])
                                        ->withInput($e->oldInput());

            }

            $remember = $request->input('remember', false);

            wp_set_auth_cookie( $user->ID, $remember, true );

            do_action( 'wp_login', $user->user_login, $user );

            return $this->redirectResponse($request);

        }

        protected function redirectResponse(Request $request) : RedirectResponse
        {

            $location = WP::adminUrl();

            if ( $request->has('redirect_to') ) {

                $location = $request->input('redirect_to');

            }

            return $this->redirector->intended($request, $location);




        }

    }