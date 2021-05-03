<?php


	namespace Tests\stubs\Middleware;

	use Closure;
	use WPEmerge\Requests\Request;

	class BarrrrrMiddleware {



		public function handle ( Request $request, Closure $next) {

			$request->body = 'bar';

			return $next($request);


		}


	}