<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Listeners;

    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Core\ShareCookies;
    use WPEmerge\Session\Events\UserLoggedIn;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;

    class ClearSessionAfterLogin
    {

        /**
         * @var StartSessionMiddleware
         */
        private $session_middleware;
        /**
         * @var ShareCookies
         */
        private $share_cookies;

        public function __construct(StartSessionMiddleware $session_middleware, ShareCookies $share_cookies)
        {

            $this->session_middleware = $session_middleware;
            $this->share_cookies = $share_cookies;
        }

        public function handleEvent(UserLoggedIn $event, Request $request, ResponseFactory $response_factory)
        {

            $session = $this->session_middleware->getSession(
                $this->share_cookies->addCookiesToRequest($request)
            );

            $session->start();
            $session->getHandler()->setRequest($request);

            $session->regenerate(true);

            $session->save();

            $response = $response_factory
                ->make()
                ->withHeader('Set-Cookie', $request->getCookies()->t);
        }


    }