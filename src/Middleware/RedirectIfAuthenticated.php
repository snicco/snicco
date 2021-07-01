<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\UrlGenerator;

    class RedirectIfAuthenticated extends Middleware {


        /**
         * @var string|null
         */
        private $url;

        /**
         * @var UrlGenerator
         */
        private $url_generator;

        public function __construct( UrlGenerator $url_generator, string $url = null )
        {
            $this->url = $url;
            $this->url_generator = $url_generator;

        }

        public function handle( Request $request, $next) :ResponseInterface {

			if ( WP::isUserLoggedIn() ) {

				$url = $this->url ?? $this->url_generator->toRoute('dashboard');

                if ($request->isExpectingJson()) {

                    return $this->response_factory
                        ->json('Only guests can access this route.')
                        ->withStatus(403);


                }

				return $this->response_factory->redirect()
                                      ->to($url);

			}

			return $next( $request );

		}

	}
