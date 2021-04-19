<?php


	namespace WPEmerge\Middleware;

	use WPEmerge\Requests\Request;

	class SubstituteModelBindings {


		public function handle ( Request $request, \Closure $next ) {

			$route = $request->getAttribute('route');

			return $next($request);

		}


	}