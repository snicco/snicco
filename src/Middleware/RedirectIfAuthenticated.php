<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Support\Url;
	use WPEmerge\Http\RedirectResponse;


	class RedirectIfAuthenticated implements Middleware {


		public function handle( RequestInterface $request, Closure $next, string $url = null ) {

			if ( is_user_logged_in() ) {

				$url = $url ?? home_url( '', $request->scheme() );

				return  new RedirectResponse( $request, 302, Url::addTrailing( $url )  ) ;

			}

			return $next($request);

		}

	}
