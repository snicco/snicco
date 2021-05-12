<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Facade\WP;
	use WPEmerge\Support\Url;
	use WPEmerge\Http\RedirectResponse;

	class RedirectIfAuthenticated implements Middleware {


		public function handle( RequestInterface $request, Closure $next, string $url = null ) {

			if ( WP::isUserLoggedIn() ) {

				$url = $url ?? WP::homeUrl( '', $request->scheme() );

				return new RedirectResponse( $request, 302, Url::addTrailing( $url ) );

			}

			return $next( $request );

		}

	}
