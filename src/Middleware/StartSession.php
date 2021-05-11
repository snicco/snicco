<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;

	class StartSession implements Middleware {


		public function handle (RequestInterface $request, \Closure $next ) {

			session_start();

			return $next($request);

		}

	}