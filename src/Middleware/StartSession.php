<?php


	namespace WPEmerge\Middleware;

	use WPEmerge\Contracts\RequestInterface;

	class StartSession {


		public function handle (RequestInterface $request, \Closure $next ) {

			session_start();

			return $next($request);

		}

	}