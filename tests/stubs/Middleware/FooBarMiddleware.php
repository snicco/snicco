<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;

	class FooBarMiddleware implements Middleware {

		public function handle( RequestInterface $request, \Closure $next, $foo = 'foo', $bar = 'bar' ) {

			if ( isset( $request->body ) ) {

				$request->body .= $foo.$bar;

				return $next( $request );
			}

			$request->body = $foo.$bar;

			return $next( $request );


		}

	}