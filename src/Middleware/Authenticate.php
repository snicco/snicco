<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;

    class Authenticate extends Middleware
    {

        /**
         * @var string|null
         */
        private $url;

        /**
         * @var ResponseFactory
         */
        private $response;

        public function __construct(ResponseFactory $response, string $url = null)
        {
            $this->url = $url;
            $this->response = $response;
        }

        public function handle(Request $request, $next)
        {

            if ( WP::isUserLoggedIn() ) {

                return $next($request);

            }

            $url = $this->url ?? WP::loginUrl($request->fullUrl(), true);

            if ($request->isAjax()) {

                return $this->response
                    ->json('Authentication Required')
                    ->withStatus(401);

            }

            return $this->response->redirect()->to($url);


        }

    }
