<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Support\WP;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\ResponseFactory;

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
