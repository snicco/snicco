<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\InvalidResponse;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\SessionManager;

    class SessionMiddleware extends Middleware
    {

        /**
         * @var SessionManager
         */
        private $manager;

        public function __construct(SessionManager $manager)
        {
            $this->manager = $manager;
        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $session = $this->manager->start($request);

            $this->manager->collectGarbage();

            return $this->handleStatefulRequest($request, $session, $next);

        }

        private function handleStatefulRequest(Request $request, Session $session, Delegate $next) : ResponseInterface
        {

            $request = $request->withSession($session);

            $response = $next($request);

            $response = $response->withCookie($this->manager->sessionCookie());

            $this->saveSession($session, $request, $response);

            return $response->withAddedHeader('Cache-Control', 'private, no-cache');

        }

        private function storePreviousUrl(ResponseInterface $response, Request $request, Session $session)
        {

            if ($response instanceof NullResponse || $response instanceof InvalidResponse) {

                return;

            }

            if ($request->isGet() && ! $request->isAjax()) {

                $session->setPreviousUrl($request->fullUrl());

            }


        }

        private function saveSession(Session $session, Request $request, ResponseInterface $response) : void
        {

            $this->storePreviousUrl($response, $request, $session);
            $request->session()->save();

        }



    }