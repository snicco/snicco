<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\ResponseFactory;

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

        public function __construct(ResponseFactory $response, ?string $url = null)
        {
            $this->url = $url;
            $this->response = $response;
        }

        public function handle(Request $request, $next):ResponseInterface
        {

            if ( WP::isUserLoggedIn() ) {

                return $next($request);

            }

            if ( $request->isExpectingJson() ) {

                return $this->response
                    ->json('Authentication Required')
                    ->withStatus(401);

            }

            $redirect_after_login = $this->url ?? $request->fullPath();

            return $this->response->redirectToLogin(true, $redirect_after_login);


        }

    }
