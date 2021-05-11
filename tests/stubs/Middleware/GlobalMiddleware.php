<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use Closure;
	use Tests\TestRequest;
	use WPEmerge\Contracts\Middleware;

	class GlobalMiddleware implements Middleware {

		const run_times = 'global_middleware';

		public function handle( TestRequest $request, Closure $next ) {

			$count = $GLOBALS['test'][ self::run_times ] ?? 0;
			$count ++;
			$GLOBALS['test'][ self::run_times ] = $count;

			$request->body = 'global_';

			return $next( $request );

		}

	}