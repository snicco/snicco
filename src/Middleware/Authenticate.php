<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Facade\WP;
	use WPEmerge\Http\RedirectResponse;


	class Authenticate implements Middleware {

		public function handle( RequestInterface $request, Closure $next, string $url = null  ) {

			if ( WP::isUserLoggedIn()  ) {

				return $next( $request );

			}

			$url = $url ?? WP::loginUrl( $request->url() );

			return new RedirectResponse($request, 302 ,$url);
		}

	}
