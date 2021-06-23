<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Authenticators;

    use WP_User;
    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class MagicLinkAuthenticator extends Authenticator
    {

        /**
         * @var MagicLink
         */
        private $magic_link;

        protected $failure_message = 'Your login link either expired or is invalid. Please request a new one.';

        public function __construct(MagicLink $magic_link)
        {
            $this->magic_link = $magic_link;
        }

        // The validation of the magic link happens in the middleware.
        public function attempt(Request $request, $next) : Response
        {

            $user = get_user_by('id', $request->query('user_id'));

            if ( ! $user instanceof WP_User ) {

                $this->fail();

            }

            return $this->login( $user, true );


        }

        private function fail () {
            $e = new FailedAuthenticationException($this->failure_message, []);
            $e->redirectToRoute('auth.login');
            throw $e;
        }

    }