<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Authenticators;

    use Snicco\Auth\Contracts\Authenticator;
    use Snicco\Auth\Exceptions\FailedAuthenticationException;
    use Snicco\Auth\Traits\ResolvesUser;
    use Snicco\Contracts\MagicLink;
    use Snicco\Http\Psr7\Request;
    use WP_User;

    class MagicLinkAuthenticator extends Authenticator
    {

        use ResolvesUser;

        private MagicLink $magic_link;

        protected string $failure_message = 'Your login link either expired or is invalid. Please request a new one.';

        public function __construct(MagicLink $magic_link)
        {

            $this->magic_link = $magic_link;
        }

        public function attempt(Request $request, $next)
        {

            $valid = $this->magic_link->hasValidSignature($request, true );

            if ( ! $valid  ) {

                $this->fail($request);

            }

            $this->magic_link->invalidate($request->fullUrl());

            $user = $this->getUserById($request->query('user_id'));

            if ( ! $user instanceof WP_User) {

                $this->fail($request);

            }

            // Whether remember_me will be allowed is determined by the config value in AuthSessionController
            return $this->login($user, true);


        }

        /**
         * @throws FailedAuthenticationException
         */
        private function fail(Request $request)
        {

            $e = new FailedAuthenticationException($this->failure_message, $request, []);
            $e->redirectToRoute('auth.login');
            throw $e;

        }

    }