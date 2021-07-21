<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Auth\AuthSessionManager;
    use Snicco\Auth\Events\Logout;
    use Snicco\Auth\Responses\LogoutResponse;
    use Snicco\Auth\WpAuthSessionToken;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Session\Session;

    class AuthenticateSession extends Middleware
    {

        /**
         * @var AuthSessionManager
         */
        private $manager;

        /**
         * @var array
         */
        private $forget_keys_on_idle;

        public function __construct(AuthSessionManager $manager, $forget_on_idle = [])
        {

            $this->manager = $manager;
            $this->forget_keys_on_idle = $forget_on_idle;
        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $session = $request->session();

            // If persistent login via cookies is enabled a we cant invalidate an idle session
            // because this would log the user out.
            // Instead we just empty out the session which will also trigger every auth confirmation middleware again.
            if ($session->isIdle($this->manager->idleTimeout())) {

                $session->forget('auth.confirm');

                foreach ($this->forget_keys_on_idle as $key) {

                    $session->forget($key);

                }

            }

            $response = $next($request);

            if ($response instanceof LogoutResponse) {

                $this->doLogout($session);

            }

            return $response;

        }

        private function doLogout(Session $session)
        {

            $user_being_logged_out = $session->userId();

            $session->invalidate();
            $session->setUserId(0);
            wp_clear_auth_cookie();
            wp_set_current_user( 0 );

            Logout::dispatch([$session, $user_being_logged_out]);

        }

    }