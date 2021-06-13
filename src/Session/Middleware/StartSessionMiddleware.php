<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Middleware;

    use Carbon\Carbon;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Events\IncomingGlobalRequest;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\Responses\InvalidResponse;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Session\Session;

    class StartSessionMiddleware extends Middleware
    {

        /**
         * @var Session
         */
        private $session;

        /**
         * @var array|int[]
         */

        /**
         * @var array
         */
        private $config;

        private $session_initialized = false;

        public function __construct(Session $session, array $config)
        {

            $this->session = $session;
            $this->config = $config;

        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $this->collectGarbage();

            /** @todo tests */
            if ( ! $this->session_initialized ) {

                $this->startSession($request);

            }

            return $this->handleStatefulRequest($request, $this->session, $next);


        }

        private function startSession(Request $request)
        {

            $cookies = $request->cookies();

            $cookie_name = $this->session->getName();

            $session_id = $cookies->get($cookie_name, '');

            $this->session->start($session_id);
            $this->session->getDriver()->setRequest($request);

            $this->session_initialized = true;


        }

        private function withSessionCookie(Response $response, Session $session) : Response
        {

            $cookie = new Cookie($this->config['cookie'], $session->getId());
            $cookie->setProperties([
                'path' => $this->config['path'],
                'samesite' => ucfirst($this->config['same_site']),
                'expires' => Carbon::now()->addMinutes($this->config['lifetime'])->getTimestamp(),
                'httponly' => $this->config['http_only'],
                'secure' => $this->config['secure'],
                'domain' => $this->config['domain']

            ]);

            return $response->withCookie($cookie);

        }

        private function handleStatefulRequest(Request $request, Session $session, Delegate $next) : ResponseInterface
        {

            $request = $request->withSession($session);

            $response = $next($request);

            $response = $this->withSessionCookie($response, $session);

            $this->saveSession($session, $request, $response);

            return $response;

        }

        private function storePreviousUrl(ResponseInterface $response, Request $request, Session $session)
        {

            if ($response instanceof NullResponse || $response instanceof InvalidResponse) {

                return;

            }

            if ( $request->isGet() && ! $request->isAjax() ) {

                $session->setPreviousUrl( $request->fullUrl() );

            }


        }

        private function saveSession(Session $session, Request $request, ResponseInterface $response) :void
        {

            /** @todo tests */
            if ( ! $this->requestRunsOnInitHook($request) ) {

                $this->storePreviousUrl($response, $request, $session);
                $session->save();
                return;

            }

            // either web-routes, admin-routes, or ajax-routes
            // will run this middleware again. Abort here to not save the session twice and mess
            // up flashed data.
            if ( $response instanceof NullResponse && $request->isRouteable() ) {

                return;

            }

            // Global route. Need to save again if flash.old is present because we might
            // have one global route and another on the next request.
            if ( $session->wasChanged() || $session->has('_flash.old') ) {

                $this->storePreviousUrl($response, $request, $session);
                $session->save();

            }


        }

        private function collectGarbage()
        {
            if ($this->configHitsLottery($this->config['lottery'])) {

                $this->session->getDriver()->gc($this->getSessionLifetimeInSeconds());

            }
        }

        private function configHitsLottery(array $lottery) : bool
        {

            return random_int(1, $lottery[1]) <= $lottery[0];
        }

        private function getSessionLifetimeInSeconds()
        {

            return $this->config['lifetime'] * 60;
        }

        private function requestRunsOnInitHook(Request $request) : bool
        {

            return $request->type() === IncomingGlobalRequest::class;

        }

    }