<?php


	namespace WPEmerge\Contracts;

	interface Middleware {


		public function handle( RequestInterface $request, \Closure $next );


	}