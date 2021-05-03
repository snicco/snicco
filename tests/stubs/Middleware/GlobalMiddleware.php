<?php


	namespace Tests\stubs\Middleware;

	use WPEmerge\Contracts\RequestInterface;

	class GlobalMiddleware {


		public function handle( RequestInterface $request, \Closure $next ) {

			if ( isset( $request->body ) ) {

				$request->body .= 'global_';

				return $next( $request );
			}

			$request->body = 'global_';

			return $next( $request );


		}


	}