<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Middleware;

	use Closure;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Exceptions\AuthorizationException;


	class Authorize implements Middleware {


		public function handle( RequestInterface $request, Closure $next, string $capability, ...$args ) {


			$args = array_merge( [$capability] , $args);


			if ( call_user_func_array( 'current_user_can', $args ) ) {

				return $next( $request );

			}

			throw new AuthorizationException('You do not have permission to perform this action');

		}

	}
