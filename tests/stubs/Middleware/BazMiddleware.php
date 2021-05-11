<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;

	class BazMiddleware implements Middleware {

		public function handle( RequestInterface $request, \Closure $next, $baz = 'baz' ) {

			if ( isset( $request->body ) ) {

				$request->body .= $baz;

				return $next( $request );
			}

			$request->body = $baz;

			return $next( $request );

		}

	}