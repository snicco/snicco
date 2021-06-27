<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Auth\AuthSessionManager;
    use WPEmerge\Auth\WpAuthSessionToken;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;

    class AuthenticateSession extends Middleware
    {

        /**
         * @var AuthSessionManager
         */
        private $manager;

        /**
         * @var array
         */
        private $forget_on_idle;

        public function __construct(AuthSessionManager $manager, $forget_on_idle = [])
        {
            $this->manager = $manager;
            $this->forget_on_idle = $forget_on_idle;
        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $session = $request->session();

            // If persistent login via cookies is enabled a we cant invalidate an idle session
            // because this would log the user out.
            // Instead we just empty out the session which will also trigger every auth confirmation middleware again.
            if ( $session->isIdle( $this->manager->idleTimeout() ) ) {

                $session->forget(array_merge(['auth.confirm'], $this->forget_on_idle));


            }

            return $next($request);

        }

    }