<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;
    use WPEmerge\Support\Url;
	use WPEmerge\Http\RedirectResponse;

	class RedirectIfAuthenticated extends Middleware {


        /**
         * @var string|null
         */
        private $url;

        public function __construct(string $url = null )
        {
            $this->url = $url;
        }

        public function handle( Request $request, $next) {

			if ( WP::isUserLoggedIn() ) {

				$url = $this->url ?? WP::homeUrl( '', $request->getUri()->getScheme() );

				// return new RedirectResponse( $request, 302, Url::addTrailing( $url ) );

			}

			return $next( $request );

		}

	}
