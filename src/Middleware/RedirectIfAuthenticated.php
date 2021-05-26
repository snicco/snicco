<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;

	class RedirectIfAuthenticated extends Middleware {


        /**
         * @var string|null
         */
        private $url;

        /**
         * @var ResponseFactory
         */
        private $response;

        public function __construct(ResponseFactory $response, string $url = null )
        {
            $this->url = $url;
            $this->response = $response;
        }

        public function handle( Request $request, $next) {

			if ( WP::isUserLoggedIn() ) {

				$url = $this->url ?? WP::homeUrl( '', $request->getUri()->getScheme() );

				return $this->response->redirect()
                                      ->to($url)
                                      ->withStatus(302);

			}

			return $next( $request );

		}

	}
