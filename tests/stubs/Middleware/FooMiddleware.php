<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;

	class FooMiddleware implements Middleware {

		public function handle( RequestInterface $request, \Closure $next, $foo = 'foo' ) {

			if ( isset( $request->body ) ) {

				$request->body .= $foo;

				return $next( $request );
			}

			$request->body = $foo;

			return $next( $request );


		}

	}