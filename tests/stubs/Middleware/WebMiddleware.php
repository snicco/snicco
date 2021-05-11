<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use Tests\TestRequest;
	use WPEmerge\Contracts\Middleware;

	class WebMiddleware implements Middleware {

		const run_times = 'web_middleware';

		public function handle( TestRequest $request, \Closure $next ) {

			$count = $GLOBALS['test'][ self::run_times ];
			$count ++;
			$GLOBALS['test'][ self::run_times ] = $count;

			return $next( $request );

		}

	}