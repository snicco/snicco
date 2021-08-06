<?php


	declare( strict_types = 1 );


	namespace Snicco\Middleware;

	use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Psr7\Request;
    use Snicco\Routing\UrlGenerator;
    use Snicco\Support\WP;

    class RedirectIfAuthenticated extends Middleware {


        private ?string $url;
        private UrlGenerator $url_generator;

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
                        ->json(['message' =>'Only guests can access this route.'])
                        ->withStatus(403);


                }

				return $this->response_factory->redirect()
                                      ->to($url);

			}

			return $next( $request );

		}

	}
