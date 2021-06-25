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
    use WPEmerge\Session\Contracts\SessionManagerInterface;

    class StartSessionMiddleware extends Middleware
    {

        /**
         * @var SessionManager
         */
        private $manager;

        public function __construct(SessionManagerInterface $manager)
        {
            $this->manager = $manager;
        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $this->manager->collectGarbage();

            $session = $this->manager->start($request, $request->userId());

            return $this->handleStatefulRequest($request, $session, $next);

        }

        private function handleStatefulRequest(Request $request, Session $session, Delegate $next) : ResponseInterface
        {

            $request = $request->withSession($session);

            $response = $next($request);

            $this->saveSession($session, $request, $response);

            $response = $response->withCookie($this->manager->sessionCookie());

            if ( ! $response->hasHeader('Cache-Control') ) {

                $response = $response->withAddedHeader('Cache-Control', 'private, no-cache');

            }

            return $response;


        }

        private function storePreviousUrl(ResponseInterface $response, Request $request, Session $session)
        {

            if ($response instanceof NullResponse || $response instanceof InvalidResponse) {

                return;

            }

            if ( $request->isGet() && ! $request->isAjax()) {

                $session->setPreviousUrl($request->fullUrl());

            }


        }

        private function saveSession(Session $session, Request $request, ResponseInterface $response) : void
        {

            $this->storePreviousUrl($response, $request, $session);
            $this->manager->save();

        }

    }