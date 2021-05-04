<?php


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Responses\RedirectResponse;


	class Authenticate implements Middleware {

		public function handle( RequestInterface $request, Closure $next, string $url = null  ) {

			if ( is_user_logged_in() ) {

				return $next( $request );

			}

			return ( new RedirectResponse($request) )->to(

				$url ?? wp_login_url( $request->getUrl() )

			);
		}

	}
