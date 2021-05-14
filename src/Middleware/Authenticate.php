<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Facade\WP;
	use WPEmerge\Http\RedirectResponse;
    use WPEmerge\Http\Request;

    class Authenticate extends Middleware {

        /**
         * @var string|null
         */
        private $url;

        public function __construct(string $url = null )
        {
            $this->url = $url;
        }

		public function handle( Request $request, $next  ) {

			if ( WP::isUserLoggedIn()  ) {

				return $next( $request );

			}

			$url = $url ?? WP::loginUrl( $request->fullUrl() );

			// return new RedirectResponse($request, 302 ,$url);
		}

	}
