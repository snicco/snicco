<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Http\RedirectResponse;


	class Authenticate implements Middleware {

		public function handle( RequestInterface $request, Closure $next, string $url = null  ) {

			if ( is_user_logged_in() ) {

				return $next( $request );

			}

			$url = $url ?? wp_login_url( $request->url() );

			return new RedirectResponse($request, 302 ,$url);
		}

	}
